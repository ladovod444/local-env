<?php

namespace App\EventListener\NewCloneInstall;

use App\Event\NewCloneInstall;
use App\Services\LocalSiteCreator;
use Psr\Log\LoggerInterface;

/**
 * Class BuildSite
 * @package AppBundle\EventListener\NewCloneInstall
 */
class BuildSite extends AbstractNewCloneInstallListener
{
    /** @var LocalSiteCreator */
    private $siteCreator;

    /** {@inheritdoc} */
    public function __construct(
        LoggerInterface $logger,
        LocalSiteCreator $siteCreator
    ) {
        $this->siteCreator = $siteCreator;
        parent::__construct($logger);
    }

    /** {@inheritdoc} */
    protected function processEvent(NewCloneInstall $newCloneInstallEvent)
    {
        $this->logger->notice('Building site');
        $this->siteCreator->cloneTheme(
            $newCloneInstallEvent->getCloneName(),
            $newCloneInstallEvent->getMasterName(),
            $newCloneInstallEvent->getSiteRepository(),
            $newCloneInstallEvent->getSiteBranch(),
            $newCloneInstallEvent->getVacantCell()
        );
    }
}
