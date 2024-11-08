<?php

namespace App\EventListener\SiteInstall;

use App\Event\SiteInstall;
use App\Model\Workspace;
use App\Services\DatabaseManager;
use App\Services\LocalSiteCreator;
use Psr\Log\LoggerInterface;

/**
 * Class ImportDatabase
 *
 * @package AppBundle\EventListener\SiteInstall
 * @deprecated
 * @TODO Rename
 */
class ImportDatabase extends AbstractSiteInstallListener
{

    /** @var DatabaseManager */
    private $db;

    /** @var LocalSiteCreator */
    private $siteCreator;

    /** {@inheritdoc} */
    public function __construct(
        LoggerInterface $logger,
        LocalSiteCreator $siteCreator,
        DatabaseManager $db
    ) {
        parent::__construct($logger);
        $this->db = $db;
        $this->siteCreator = $siteCreator;
    }

    /** {@inheritdoc} */
    protected function processEvent(SiteInstall $siteInstallEvent)
    {
        $databaseName = $this->siteCreator->buildDatabaseName(
            $siteInstallEvent->getSiteServerInfo()->getSiteId(),
            $siteInstallEvent->getVacantCell()
        );

        $this->logger->notice('Preparing database.');

        if (!$this->db->databaseExist($databaseName)) {
            $this->logger->notice(
                sprintf('Database "%s" not exist.', $databaseName)
            );
            $this->db->create($databaseName);
        } else {
            $this->logger->notice(
                sprintf('Database "%s" already exist.', $databaseName)
            );
            $this->logger->notice(sprintf('Cleaning up "%s"', $databaseName));
            $this->db->truncate($databaseName);
        }
    }
}
