<?php

namespace App\EventListener\SiteCleanup;

use App\Event\EnvironmentCleanup;
use App\Services\DatabaseManager;
use App\Services\LocalSiteManager;
use Psr\Log\LoggerInterface;

/**
 * Class PreCleanup
 *
 * @package AppBundle\EventListener\SiteCleanup
 */
class PreCleanup extends AbstractSiteCleanupListener
{

    /**
     * @var LocalSiteManager
     */
    private $localSiteManager;

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * PreCleanup constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param LocalSiteManager         $localSiteManager
     * @param DatabaseManager          $databaseManager
     */
    public function __construct(LoggerInterface $logger, LocalSiteManager $localSiteManager, DatabaseManager $databaseManager)
    {
        parent::__construct($logger);
        $this->localSiteManager = $localSiteManager;
        $this->databaseManager = $databaseManager;
    }

    /**
     * @param \App\Event\EnvironmentCleanup $event
     *
     * @return mixed|void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function processEvent(EnvironmentCleanup $event)
    {
        $this->logger->info(sprintf('The following site will be deleted: %s', $event->getPathToSite()));

        $canonicalDatabaseName = $event->getCanonicalDatabaseName();
        $databasesCount = $this->databaseManager->getClonesCount($canonicalDatabaseName);
        if($this->databaseManager->databaseExist($canonicalDatabaseName)){
            $databasesCount += 1;
        }

        if(empty($databasesCount)){
            return;
        }

        $clones = $this->databaseManager->getDatabaseClonesNames($canonicalDatabaseName);
        $databases = array_merge([$canonicalDatabaseName], $clones);
        $this->logger->info(sprintf('The following %d database(s) will be deleted: %s', $databasesCount, implode(', ', $databases)));
        sleep(3);
    }
}
