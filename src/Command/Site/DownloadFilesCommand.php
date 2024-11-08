<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Model\Workspace;
use App\Services\SiteInfoExtractor;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use App\Process\SshCheckTrait;

/**
 * Class DownloadFilesCommand
 *
 * @package AppBundle\Command\Site
 */
class DownloadFilesCommand extends AbstractCommand
{

    use SshCheckTrait;

    /** @var Filesystem */
    private $fileSystem;

    /** @var SiteInfoExtractor */
    private $siteInfoExtractor;

    /** @var string */
    private $workspace;

    /** {@inheritdoc} */
    public function __construct(
        $name,
        SiteInfoExtractor $siteInfoExtractor,
        Filesystem $filesystem
    ) {
        if (is_null($name)) {
            $name = NULL;
        }
        $this->fileSystem = $filesystem;
        $this->siteInfoExtractor = $siteInfoExtractor;
        $this->workspace = Workspace::FILES_DIR;
        parent::__construct($name = null);
    }

  /** {@inheritdoc} */
  protected function configure()
  {
    $this
      ->setName('app:site:download:files')
      ->setDescription('Download files')
      ->addOption('url', null, InputOption::VALUE_REQUIRED)
      ->addOption('site', 's', InputOption::VALUE_OPTIONAL)
      ->addOption('onlyPrivate', 'op', InputOption::VALUE_OPTIONAL)
      ->addOption('vault', 'va', InputOption::VALUE_OPTIONAL);
  }

    /** {@inheritdoc} */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize($input, $output);
        $url = $input->getOption('url');
        if (empty($url)) {
            $this->stopExecution('--url option is required.');
        }
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

    $siteUrl = $input->getOption('url');
    $siteUrl = trim($siteUrl, '/');
    $vault = $input->getOption('vault');
    $this->io->title(
      sprintf('Trying to copy files for the site %s', $siteUrl)
    );

        try {
            $monitorDataInfo = $this->siteInfoExtractor->getMonitorInfo(
                $siteUrl
            );

            $serverInfo = $this->siteInfoExtractor->getServerInfo($siteUrl);

            // @TODO Refactor
            $site = $monitorDataInfo['site'];
            $environment = $serverInfo->getEnvironment();
            $site = str_replace($environment, '', $site);

            $this->io->success(
                sprintf('Hostname: %s', $monitorDataInfo['hostname'])
            );
            $this->io->success(sprintf('Subscription: %s', $site));

            $user = $site.'.'.$environment;
            $host = $monitorDataInfo['hostname'];

            if (!$this->checkSshConnection($user, $host)) {
                $this->io->title('Acquia: Public Key Authentication Failed');
                exit;
            }

            $pathToFiles = sprintf('%s', Workspace::FILES_DIR);

            $privateFolder = '';
            $onlyPrivate = $input->getOption('onlyPrivate');
            if($onlyPrivate == true){
              $privateFolder = '/private';
            }

      $siteName = $input->getOption('site');
      if (!empty($siteName)) {
        $siteId = substr($siteName, 0, strpos($siteName, '_'));
        $siteId = str_replace('-', '_', $siteId);
        $vaultPath = '/home/developer/cluster/' . $siteName . '/sites/' . $siteId . '/src';
        $sitePath = '/home/developer/cluster/' . $siteName . '/sites/' . $siteId . '/src/files';
        $pathToFiles = realpath($sitePath);
        if(!empty($pathToFiles)) {
          $cmd = sprintf('sudo chmod 775 %s', $pathToFiles);
          $process = new Process($cmd);
          $process->run();
          if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
          }
        }
        else {
          $this->io->error(sprintf('Please make sure %s directory exists', $sitePath));
          exit;
        }
      }

            $this->fileSystem->remove($pathToFiles);
            $this->fileSystem->mkdir($pathToFiles . $privateFolder);
            $this->fileSystem->chmod($pathToFiles, 0775);

            $sourcePath = strtr(
                '%user%@%host%:/mnt/gfs/home/%site%/%env%/sites/%site_id%/files%private_folder%/* %site_path%%private_folder%',
                [
                    '%user%' => $user,
                    '%host%' => $host,
                    '%site%' => $site,
                    '%env%' => $environment,
                    '%site_id%' => $serverInfo->getSiteId(),
                    '%site_path%' => $pathToFiles,
                    '%private_folder%' => $privateFolder,
                ]
            );

      if(!empty($vault)) {
        $sourcePath = strtr(
          '%user%@%host%:/mnt/gfs/%site%.%env%/nobackup/files/private/%site_id%/* %vault_path%',
          [
            '%user%' => $user,
            '%host%' => $host,
            '%site%' => $site,
            '%env%' => $environment,
            '%site_id%' => $serverInfo->getSiteId(),
            '%vault%' => $vault,
            '%vault_path%' => $vaultPath,
          ]
        );
      }

      $cmd = sprintf(
        "rsync --exclude-from file-sync-exclude.txt -avz -e ssh %s",
        $sourcePath
      );

            $this->io->writeln('Executing command: '.$cmd);
            $process = new Process($cmd);
            $process->setTimeout(null);
            $progress = new ProgressBar($output);
            $progress->start();
            $progress->setFormat(
                'File: %current% [%bar%] Elapsed time: %elapsed:6s%'
            );
            $process->run(
                function ($type, $buffer) use ($progress) {
                    $progress->advance();
                }
            );
            $progress->finish();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $this->io->title(
                sprintf('Files are copied to %s.', $pathToFiles . $privateFolder)
            );

        } catch (\Exception $exception) {
            $exceptionMessage = sprintf(
                'File sync failed. Error message: %s. %s',
                $exception->getMessage(),
                $exception->getTraceAsString()
            );
            $this->stopExecution($exceptionMessage);
        }

    }
}
