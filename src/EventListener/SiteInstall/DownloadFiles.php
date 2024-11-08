<?php

namespace App\EventListener\SiteInstall;

use App\Command\Site\DownloadFilesCommand;
use App\Event\SiteInstall;
use App\Services\DatabaseManager;
use App\Services\LocalSiteCreator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Model\Workspace;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class DownloadFiles
 *
 * @package AppBundle\EventListener\SiteInstall
 */
class DownloadFiles extends AbstractSiteInstallListener
{

    /**
     * @var DatabaseManager
     */
    private $db;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Services\LocalSiteCreator
     */
    private $localSiteCreator;

    /**
     * @var \App\Command\Site\DownloadFilesCommand
     */
    private $downloadFilesCommand;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        LocalSiteCreator $localSiteCreator,
        DownloadFilesCommand $downloadFilesCommand,
        DatabaseManager $db
    ) {
        parent::__construct($logger);
        $this->em = $entityManager;
        $this->localSiteCreator = $localSiteCreator;
        $this->downloadFilesCommand = $downloadFilesCommand;
        $this->db = $db;
    }

  /** {@inheritdoc} */
  protected function processEvent(SiteInstall $siteInstallEvent)
  {
    $sitePath = $this->localSiteCreator->getSiteInDocrootDirectory(
      $siteInstallEvent->getSiteServerInfo()->getSiteId(),
      $siteInstallEvent->getVacantCell()
    );
    $url = $siteInstallEvent->getSiteServerInfo()->getSiteUrl();
    $site = $this->localSiteCreator->getUniqueId(
      $siteInstallEvent->getSiteServerInfo()->getSiteId(),
      $siteInstallEvent->getVacantCell()
    );

    //Download Vaut config here
    if ($this->vaultIsEnabled($sitePath, $site)) {
      $arguments = [
        'command' => 'app:site:download:files',
        '--url' => $url,
        '--site' => $site,
        '--vault' => Workspace::VAULT_CONFIG,
      ];

      try{
        $this->logger->notice("Starting Vault keys downloading.");
        $commandInput = new ArrayInput($arguments);
        $this->downloadFilesCommand->run($commandInput, new ConsoleOutput());
      } catch (\Exception $exception) {
        throw new \Exception(sprintf('Vault sync failed. Message: %s', $exception->getMessage()));
      }
    }

    if (!trim(shell_exec("(cd " . $sitePath . " && drush sql-query \"SHOW TABLES LIKE 'encrypt_config';\")"))){
      $this->logger->notice("The site doesn't require encryption keys.");
      return ;
    }

        $this->logger->notice('Downloading files');

        $output = new ConsoleOutput();
        $commandInput = new ArrayInput(array());
        $comm = new QuestionHelper();
        $question = new ChoiceQuestion(
            'Current site requires encryption keys, do you want to download files right now?',
            ["Sync all files", "Sync only 'private' folder with keys", "No"],
            2
        );
        $question->setErrorMessage('Please enter a valid number.');
        $answer = $comm->ask($commandInput, $output, $question);
    
    $onlyPrivate = false;
    if ($answer == "Sync only 'private' folder with keys") {
      $onlyPrivate = true;
    }
    elseif ($answer == "No") {
      return ;
    }
    $arguments = [
      'command' => 'app:site:download:files',
      '--url' => $url,
      '--site' => $site,
      '--onlyPrivate' => $onlyPrivate,
    ];
    try{
      $commandInput = new ArrayInput($arguments);
      $this->downloadFilesCommand->run($commandInput, $output);
    } catch (\Exception $exception) {
      throw new \Exception(sprintf('Files sync failed. Message: %s', $exception->getMessage()));
    }
  }

  /** {@inheritdoc} */
    private function vaultIsEnabled ($sitePath, $site)
    {
        $request = strtr (
            "cd %sitePath% && drush sql-query \"SELECT status FROM %site%.system WHERE name = 'jjbos_vault';\"",
            [
                '%sitePath%' => $sitePath,
                '%site%' => str_replace('-', '_', $site),
            ]
        );
        $jjbosVaultModule = trim(shell_exec($request));
        if ($jjbosVaultModule == 1 && !file_exists($sitePath.'/'.Workspace::VAULT_CONFIG)) {
            return true;
        }
        else {
            return false;
        }
    }
}
