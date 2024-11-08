<?php

namespace App\Model;

/**
 * Represents the site server model for whitesite.
 */
class WhitesiteServerInfo implements SiteServerInfoInterface
{

    /**
     * The platform version.
     *
     * @var string
     */
    private $platformVersion;

    /**
     * The site id.
     *
     * @var string
     */
    private $siteId;

    /**
     * Constructs the site server model for whitesite.
     *
     * @param string $platformVersion
     *   The platform version.
     * @param string $siteId
     *   The site id.
     */
    public function __construct($platformVersion, $siteId) {
        $this->platformVersion = $platformVersion;
        $this->siteId = $siteId;
    }

    /**
     * {@inheritdoc}
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPlatformVersion() {
        return $this->platformVersion;
    }
}
