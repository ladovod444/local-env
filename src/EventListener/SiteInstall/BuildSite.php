<?php

namespace App\EventListener\SiteInstall;

use App\Event\SiteInstall;
use App\Services\LocalSiteCreator;
use Psr\Log\LoggerInterface;

/**
 * Represents the build platform event.
 */
class BuildSite extends AbstractSiteInstallListener
{
    /**
     * The local site creator.
     *
     * @var \App\Services\LocalSiteCreator
     */
    private $siteCreator;

    /**
     * {@inheritdoc}
     */
    public function __construct(LoggerInterface $logger, LocalSiteCreator $siteCreator) {
        parent::__construct($logger);
        $this->siteCreator = $siteCreator;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEvent(SiteInstall $siteInstallEvent)
    {
        $this->siteCreator->createSite(
            $siteInstallEvent->getSiteServerInfo()->getSiteId(),
            $siteInstallEvent->getSiteRepository(),
            $siteInstallEvent->getSiteBranch(),
            $siteInstallEvent->getVacantCell()
        );
    }
}
