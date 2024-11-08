<?php

namespace App\Event;

use App\Model\SiteServerInfoInterface;

/**
 * Represents an event for whitesite installation.
 */
class WhitesiteInstall extends SiteInstall
{

    const PHASE_BUILD_WHITESITE = 'site.install.whitesite';

    /**
     * {@inheritdoc}
     */
    public function __construct(SiteServerInfoInterface $siteServerInfo, $siteRepository, $siteBranch, $platformVersion) {
        parent::__construct($siteServerInfo, $siteRepository, $siteBranch, $platformVersion);
    }
}
