<?php

namespace App\EventListener\SiteInstall;

use App\Command\Site\SyncDatabaseCommand;
use App\Event\SiteInstall;
use App\Services\LocalSiteCreator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class SynchronizeDatabase
 *
 * @package AppBundle\EventListener\SiteInstall
 */
class SynchronizeDatabase extends AbstractSiteInstallListener
{

    /**
     * @var \App\Services\LocalSiteCreator
     */
    private $localSiteCreator;

    /**
     * @var \App\Command\Site\SyncDatabaseCommand
     */
    private $command;

    public function __construct(
        LoggerInterface $logger,
        LocalSiteCreator $localSiteCreator,
        SyncDatabaseCommand $command
    ) {
        parent::__construct($logger);
        $this->localSiteCreator = $localSiteCreator;
        $this->command = $command;
    }

    /** {@inheritdoc} */
    protected function processEvent(SiteInstall $siteInstallEvent)
    {
        $this->logger->notice('Downloading database');

        $arguments = [
            'command' => 'app:site:database-sync',
            '--source-url' => $siteInstallEvent->getSiteServerInfo()->getSiteUrl(),
            '--target' => $this->localSiteCreator->getUniqueId(
                $siteInstallEvent->getSiteServerInfo()->getSiteId(),
                $siteInstallEvent->getVacantCell()
            ),
        ];

        try {
            $output = new BufferedOutput();
            $commandInput = new ArrayInput($arguments);
            $this->command->run($commandInput, $output);
            $this->logger->notice($output->fetch());
        } catch (\Exception $exception) {
            throw new \Exception(sprintf('Database sync failed. Message: %s', $exception->getMessage()));
        }
    }
}
