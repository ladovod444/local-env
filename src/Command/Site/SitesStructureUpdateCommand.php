<?php

namespace App\Command\Site;

use App\Model\Workspace;
use App\Services\GitDriver;
use App\Services\LocalDomainBuilder;
use App\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Updates sites structure to be compatible with new features.
 */
class SitesStructureUpdateCommand extends AbstractCommand
{

    const dirSepDefault = '_';

    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Provides basic utility to manipulate the file system.
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * The git driver.
     *
     * @var \App\Services\GitDriver
     */
    private $git;

    /**
     * The domain builder.
     *
     * @var \App\Services\LocalDomainBuilder
     */
    private $domainBuilder;

    /**
     * The cluster directory path.
     *
     * @var string
     */
    private $clusterDirectory;

    /**
     * {@inheritdoc}
     */
    public function __construct(LoggerInterface $logger, Filesystem $filesystem, GitDriver $gitDriver, LocalDomainBuilder $domainBuilder)
    {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->git = $gitDriver;
        $this->domainBuilder = $domainBuilder;
        $this->clusterDirectory = realpath(getcwd() . '/../cluster');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:sites-structure-update')
            ->setDescription('Updates sites structure to be compatible with new features.')
            ->setHelp('Rid off platform version from the domains, directories etc.');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {

        $this->io->caution('You are about to run update command. Please, read a message below before start!');

        $output->writeln([
            'This command helps you to update local sites to a new structure in order to use newest manager features.',
            'The sites form a list below will be affected (it is recommended to create a backup of them or ~/cluster directory at all):',
            ''
        ]);

        $depSites = $this->getDepSites();
        sort($depSites);

        for ($counter = 1; $counter <= count($depSites); $counter++) {
            $output->writeln(["\t {$counter}. {$depSites[$counter - 1]}"]);
        }

        $output->writeln(['']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->io->confirm('Do you want to continue?', true)) {
            return;
        }

        $hosts = [];
        $deprecatedSites = $this->getDepSites();

        foreach ($deprecatedSites as $fullPath) {
            $dirName = substr($fullPath, strripos($fullPath, '/') + 1);

            $dirSep = $this->getDirSeparator($dirName);
            $siteId = strstr($dirName, $dirSep, TRUE);
            $siteId = str_replace('_', '-', $siteId);
            $siteVersion = substr(strstr($dirName, $dirSep), 1);

            $cell = $this->determineVacantCell($siteId);
            $version = $this->fetchVersion($siteVersion, $dirSep);
            $newDir = $this->clusterDirectory . '/'. $siteId . '_' . $cell;

            // Change the directory structure to the valid one and copy .git
            // directory from the platform.
            $this->logger->notice(PHP_EOL . sprintf('Move %s to %s', $dirName, $newDir));
            $this->filesystem->rename($fullPath, $newDir);
            if (!$this->filesystem->exists($newDir . '/.git')) {
              $this->filesystem->mirror(Workspace::PLATFORM_DIR . '/.git', $newDir . '/.git');

              try {
                  // Try to checkout to specific version.
                  $gitWrapper = $this->git->getWrapper();
                  $gitWrapper->workingCopy($newDir)->checkout($version);
              }
              catch (\Exception $e) {
                  // Leave as is!
              }
            }

            // Update the sites.local.php file.
            $domain = $this->domainBuilder->buildDomainName($siteId, $cell);

            $fileContent = '<?php' . PHP_EOL;
            $fileContent .= sprintf("\$sites['%s'] = '%s';" . PHP_EOL, $domain, str_replace('-', '_', $siteId));
            $fileContent .= sprintf('// Created at: %s', date('d.m.Y - h:i:s')) . PHP_EOL;

            $targetPath = sprintf('%s/docroot/sites/sites.local.php', $newDir);
            $this->filesystem->dumpFile($targetPath, $fileContent);
            $hosts[] = sprintf('192.168.33.10 %s', $domain);

            // Update a symlink.
            $this->filesystem->symlink(
                $newDir . '/sites/' . str_replace('-', '_', $siteId) . '/src',
                $newDir . '/docroot/sites/' . str_replace('-', '_', $siteId)
            );
        }

        if ($hosts) {
            $this->io->note("Please add the following to your hosts file:");
            $output->writeln(implode(PHP_EOL, $hosts));
        }
    }

    /**
     * Helper function to get a list of deprecated sites.
     *
     * @return array
     *   A list of deprecated sites.
     */
    protected function getDepSites() {
        $clusterDirectory = glob($this->clusterDirectory . '/*', GLOB_ONLYDIR);

        return array_filter($clusterDirectory, function($dir) {
            return preg_match('/(whitesite(_|-)master|(-|_)v.+?)/', $dir);
        });
    }

    /**
     * Get the directory separator.
     */
    protected function getDirSeparator($dirName) {
        $sepKey = substr_count($dirName, static::dirSepDefault) === 1;
        return $sepKey ? static::dirSepDefault : '-';
    }

    /**
     * Determines a vacant cell.
     */
    protected function determineVacantCell($siteId) {
        $cell = 0;
        $sites = glob($this->clusterDirectory . '/*', GLOB_ONLYDIR);

        // Increase a counter until free name will be found.
        // @todo: shouldn't we increase last counter only?
        do {
          $cell++;
        }
        while (in_array($this->clusterDirectory . '/' . $siteId . '_' . $cell, $sites));

        return $cell;
    }

    /**
     * Fetch a platform version.
     */
    protected function fetchVersion($dirVersion, $dirSep) {
        if ($dirVersion === 'master') {
            return $dirVersion;
        }

        $sep = ($dirSep === self::dirSepDefault) ? '-' : self::dirSepDefault;

        $parts = explode($sep, $dirVersion);
        $last = array_pop($parts);

        $newVersion = implode('.', $parts);

        return is_string($last) ? $newVersion . '-' . $last : $newVersion;
    }
}
