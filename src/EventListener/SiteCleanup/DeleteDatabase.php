<?php

namespace App\EventListener\SiteCleanup;

use App\Event\EnvironmentCleanup;
use App\Services\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Class DeleteDatabase
 *
 * @package AppBundle\EventListener\SiteCleanup
 */
class DeleteDatabase extends AbstractSiteCleanupListener
{

    /**
     * @var \App\Services\DatabaseManager
     */
    private $databaseManager;

    /**
     * DeleteDatabase constructor.
     *
     * @param \Psr\Log\LoggerInterface            $logger
     * @param \App\Services\DatabaseManager $databaseManager
     */
    public function __construct(LoggerInterface $logger, DatabaseManager $databaseManager)
    {
        parent::__construct($logger);
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
        $canonicalDatabaseName = $event->getCanonicalDatabaseName();

        $databasesCount = $this->databaseManager->getClonesCount($canonicalDatabaseName);
        if($this->databaseManager->databaseExist($canonicalDatabaseName)){
            $databasesCount += 1;
        }

        if(empty($databasesCount)){
            $this->logger->info('Databases not found. Nothing to delete.');
            return;
        }

        $clones = $this->databaseManager->getDatabaseClonesNames($canonicalDatabaseName);
        foreach ($clones as $cloneName) {
            $this->logger->info(sprintf('Deleting clone: %s', $cloneName));
            $this->databaseManager->deleteDatabase($cloneName);
        }
        $this->logger->info(sprintf('Deleting original database: %s', $canonicalDatabaseName));
        $this->databaseManager->deleteDatabase($canonicalDatabaseName);

    }
}
