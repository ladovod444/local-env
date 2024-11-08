<?php

namespace App\Event;

use App\Model\SiteServerInfoInterface;
use App\Model\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents a base event for site installation.
 */
class SiteInstall extends Event
{
    
    const PHASE_BUILD_PLATFORM = 'site.install.build_platform';

    const PHASE_POST_PUBLISH = 'site.install.post_publish';

    /**
     * The site server model.
     *
     * @var \App\Model\SiteServerInfoInterface
     */
    private $siteServerInfo;

    /**
     * The site repository.
     *
     * @var string
     */
    private $siteRepository;

    /**
     * The site branch.
     *
     * @var string
     */
    private $siteBranch;

    /**
     * The platform version.
     *
     * @var string
     */
    private $platformVersion;

    /**
     * The vacant cell.
     *
     * @var int
     */
    private $cell;

    /**
     * Constructs the SiteInstall object.
     *
     * @param SiteServerInfoInterface $siteServerInfo
     *   The site server model.
     * @param string $siteRepository
     *   The site repository.
     * @param string $siteBranch
     *   The site branch.
     * @param string $platformVersion
     *   The platform version.
     */
    public function __construct(SiteServerInfoInterface $siteServerInfo, $siteRepository, $siteBranch, $platformVersion) {
        $this->siteServerInfo = $siteServerInfo;
        $this->siteRepository = $siteRepository;
        $this->siteBranch = $siteBranch;
        $this->platformVersion = $platformVersion;

        // Determine a vacant cell
        $this->cell = $this->determineVacantCell();
    }

    /**
     * Get site server information.
     *
     * @return \App\Model\SiteServerInfoInterface
     */
    public function getSiteServerInfo()
    {
        return $this->siteServerInfo;
    }

    /**
     * Get the git repository.
     *
     * @return string
     */
    public function getSiteRepository()
    {
        return $this->siteRepository;
    }

    /**
     * Get the site branch.
     *
     * @return string
     */
    public function getSiteBranch()
    {
        return $this->siteBranch;
    }

    /**
     * Get the platform version.
     *
     * @return string
     */
    public function getplatformVersion()
    {
        return $this->platformVersion;
    }

    /**
     * Get the target site id.
     *
     * @return string
     */
    public function getTergetSiteId() {
        return str_replace('_', '-', $this->siteServerInfo->getSiteId());
    }

    /**
     * Get master site.
     *
     * @return string
     */
    public function getMasterSite() {
      preg_match_all ('/([^\/]+)\.git$/', $this->siteRepository, $matches);
      return $matches[1][0];
    }

    /**
     * Helper function to get target directory.
     *
     * @return string
     *   The target directory.
     */
    public function getTargetDirectory() {
        return Workspace::CLUSTER_DIR . '/'. $this->getTergetSiteId() . '_' . $this->getVacantCell();
    }


    /**
     * Get the vacant cell.
     *
     * @return int
     */
    public function getVacantCell() {
        return $this->cell;
    }

    /**
     * Determines a vacant cell
     *
     * @return int
     */
    protected function determineVacantCell() {
        $cell = 0;

        $sites = glob(Workspace::CLUSTER_DIR . '/*', GLOB_ONLYDIR);
        $siteId = $this->getTergetSiteId();

        // Increase a counter until free name will be found.
        // @todo: shouldn't we increase last counter only?
        do {
          $cell++;
        }
        while (in_array(Workspace::CLUSTER_DIR . '/'. $siteId . '_' . $cell, $sites));

        return $cell;
    }
}
