<?php

namespace App\Services;

use App\Model\ConsoleCommand;
use App\Model\Workspace;
use App\Process\ProcessAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Underscore\Types\Arrays;
use App\Process\SshCheckTrait;

/**
 *
 */
const BASE_URL = 'https://cloudapi.acquia.com';
/**
 *
 */
const BASE_PATH = '/v1';

/**
 * Class DatabaseInfoExtractor
 *
 * @package App\Services
 */
class DatabaseInfoExtractor
{

    use ProcessAwareTrait;

    use SshCheckTrait;

    /**
     * @var DataProviderBridge
     */
    private $dataProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var Filesystem */
    private $filesystem;

    /**
     * DatabaseInfoExtractor constructor.
     *
     * @param DataProviderBridge $dataProvider
     * @param LoggerInterface    $logger
     * @param Filesystem         $filesystem
     */
    public function __construct(DataProviderBridge $dataProvider, LoggerInterface $logger, Filesystem $filesystem)
    {
        $this->dataProvider = $dataProvider;
        $this->logger = $logger;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $siteUrl
     *
     * @return mixed
     * @throws \Exception
     */
    public function saveDatabaseDump($siteUrl)
    {
        $subscription = $this->dataProvider->getLocationPropertyByUrl($siteUrl, 'subscription');
        $environment = $this->dataProvider->getLocationPropertyByUrl($siteUrl, 'environment');
        $docroot = $this->dataProvider->getEnvironmentPropertyByHostName($siteUrl, 'root');

        $definitions = $this->dataProvider->getEnvironmentPropertyByHostName($siteUrl, 'site_definitions');
        $domain = parse_url($siteUrl, PHP_URL_HOST);
        foreach ($definitions as $definition) {
            foreach($definition->locales as $locale_key => $locale) {
                $urls = Arrays::pluck($locale->domains, 'url');
                if (Arrays::contains($urls, $domain)) {
                    $siteDefinition = $definition;
                    break;
                }
            }
        }

        if (empty($siteDefinition)) {
            throw new \Exception('Could not find site definition.');
        }

        // Prepare backup directory.
        $this->prepareBackupDir();

        $environmentSettings = $this->dataProvider->getEnvironmentPropertyByHostName($siteUrl, 'settings');
        $remoteUser = $this->propertyAccessor->getValue($environmentSettings, 'remote_user');
        $remoteHost = $this->propertyAccessor->getValue($environmentSettings, 'remote_host');

        if (!$this->checkSshConnection($remoteUser, $remoteHost)) {
            $this->logger->error('Acquia: Public Key Authentication Failed');
            exit;
        }

        $siteLocation = $this->propertyAccessor->getValue($siteDefinition, 'package.location');

        $connectionString = strtr('%user%@%host%', ['%user%' => $remoteUser, '%host%' => $remoteHost]);
        $remotePath = strtr('%docroot%/sites/%location%', ['%docroot%' => $docroot, '%location%' => $siteLocation]);
        $backupFileName = sprintf('%s_%s_%s_dump.sql', $siteLocation, $subscription, $environment);
        $backupFileNameArchive = sprintf('%s_%s_%s_dump.sql.gz', $siteLocation, $subscription, $environment);

        $remotePathToBackupFile = sprintf('/tmp/%s', $backupFileName);
        $remotePathToBackupFileArchive = sprintf('/tmp/%s', $backupFileNameArchive);

        $remoteCommands = [
            sprintf('rm %s', $remotePathToBackupFileArchive),
            sprintf('cd %s', $remotePath),
            'drush core-status',
            sprintf('drush sql-dump --result-file=%s --gzip', $remotePathToBackupFile),
        ];

        $createDatabaseDumpCommandLine = sprintf("ssh %s '%s'", $connectionString, implode('; ', $remoteCommands));
        $this->logger->info('Dumping database.');
        exec($createDatabaseDumpCommandLine, $remoteOutput);
        $this->logOutput($remoteOutput);
        $this->logger->info('Dumping has been completed.');

        $this->downloadDump($connectionString, $remotePathToBackupFileArchive);

        $this->extractDump($backupFileNameArchive);

        return Workspace::DB_DIR.'/'.$backupFileName;
    }

    /**
     * @param string $connectionString
     * @param string $remotePathToBackupFileArchive
     */
    private function downloadDump($connectionString, $remotePathToBackupFileArchive)
    {
        $this->logger->info('Downloading dump.');

        $cmd = sprintf('scp %s:%s %s', $connectionString, $remotePathToBackupFileArchive, Workspace::DB_DIR);
        (new Process($cmd))
            ->setTimeout(ConsoleCommand::PROCESS_TIMEOUT)
            ->run($this->getRealTimeProcessCallback());

        $this->logger->info('Dump has been downloaded.');
    }

    /**
     * @param string $archiveName
     */
    private function extractDump($archiveName)
    {
        $this->logger->info('Extracting archive.');

        $cmd = sprintf('gunzip -f %s', Workspace::DB_DIR.'/'.$archiveName);
        (new Process($cmd))
            ->setTimeout(ConsoleCommand::PROCESS_TIMEOUT)
            ->run($this->getRealTimeProcessCallback());
        $this->logger->info('Extracting has been completed.');
    }

    /**
     * @param $output
     */
    private function logOutput($output)
    {
        foreach ($output as $line) {
            $this->logger->info($line);
        }
    }

    /**
     * Prepares backup directory to keep db dumps.
     */
    private function prepareBackupDir()
    {
        if (!$this->filesystem->exists(Workspace::DB_DIR)) {
            $this->filesystem->mkdir(Workspace::DB_DIR);
        }
    }
}
