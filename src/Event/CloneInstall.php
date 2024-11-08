<?php

namespace App\Event;

use App\Model\SiteServerInfoInterface;

/**
 * Represents an event for clone installation.
 */
class CloneInstall extends SiteInstall
{

    const PHASE_BUILD_SITE = 'site.install.build_site';

    const PHASE_DOWNLOAD_DATABASE = 'site.install.download_database';

    const PHASE_DOWNLOAD_FILES = 'site.install.download_files';

    /**
     * {@inheritdoc}
     */
    public function __construct(SiteServerInfoInterface $siteServerInfo, $siteRepository, $siteBranch, $platformVersion) {
        parent::__construct($siteServerInfo, $siteRepository, $siteBranch, $platformVersion);
    }
}
