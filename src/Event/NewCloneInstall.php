<?php

namespace App\Event;

use App\Model\SiteServerInfoInterface;

/**
 * Represents an event for new clone installation.
 */
class NewCloneInstall extends SiteInstall
{

    const PHASE_BUILD_SITE = 'newClone.install.build_site';

    /**
     * The new clone master name.
     *
     * @var string
     */
    private $masterName;

    /**
     * {@inheritdoc}
     */
    public function __construct(SiteServerInfoInterface $siteServerInfo, $siteRepository, $siteBranch, $platformVersion, $masterName) {
        parent::__construct($siteServerInfo, $siteRepository, $siteBranch, $platformVersion);

        $this->masterName = $masterName;
    }

    /**
     * Get the clone name.
     *
     * @deprecated
     *   Use site server info instead.
     */
    public function getCloneName()
    {
        return $this->getSiteServerInfo()->getSiteId();
    }

    /**
     * Get the platform tag.
     *
     * @deprecated
     *   Use site server info instead.
     */
    public function getPlatformTag()
    {
        return $this->getSiteServerInfo()->getDefaultPlatformVersion();
    }

    /**
     * Get the new clone master name.
     *
     * @return string
     */
    public function getMasterName()
    {
        return $this->masterName;
    }
}
