<?php

namespace App\Model;

/**
 * Class SiteServerInfo
 *
 * @package AppBundle\Model
 */
class SiteServerInfo implements SiteServerInfoInterface
{

    /** @var string */
    private $environment;

    /** @var string */
    private $platformVersion;

    /** @var string */
    private $siteId;

    /** @var string */
    private $siteUrl;

    /** @var string */
    private $theme;

    /**
     * SiteServerInfo constructor.
     *
     * @param $siteUrl
     * @param $environment
     * @param $platformVersion
     * @param $siteId
     * @param $theme
     */
    public function __construct(
        $siteUrl,
        $environment,
        $platformVersion,
        $siteId,
        $theme
    ) {
        $this->environment = $environment;
        $this->platformVersion = $platformVersion;
        $this->siteId = $siteId;
        $this->siteUrl = $siteUrl;
        $this->theme = $theme;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return string
     */
    public function getPlatformVersion()
    {
        return $this->platformVersion;
    }

    /**
     * @return string
     *
     * @deprecated
     *   Use SiteServerInfo::getDefaultPlatformVersion() instead!
     */
    public function getPlatformTag()
    {
        return sprintf('v%s', $this->platformVersion);
    }

    /**
     * @return string
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @return string
     */
    public function getSiteUrl()
    {
        return $this->siteUrl;
    }

    /**
     * Get the platform version.
     *
     * @return string
     *   The platform version.
     */
    public function getDefaultPlatformVersion()
    {
        return sprintf('v%s', $this->platformVersion);
    }

    /**
     * Get the theme name.
     *
     * @return string
     *   The theme name.
     */
    public function getThemeName()
    {
        return $this->theme;
    }
}
