<?php

namespace App\Services;

use App\Model\Workspace;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class LocalSiteManager
 *
 * @package AppBundle\Services
 */
class LocalSiteManager
{

    /**
     * The directory's default separator.
     *
     * @var string
     */
    const DIR_SEP_DEFAULT = '_';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /** @var GitDriver */
    private $git;

    /**
     * LocalSiteManager constructor.
     *
     * @param Filesystem $filesystem
     * @param GitDriver $gitDriver
     */
    public function __construct(Filesystem $filesystem, GitDriver $gitDriver)
    {
        $this->filesystem = $filesystem;
        $this->git = $gitDriver;
    }

    /**
     *
     * @param string $localSiteName
     *
     * @return string
     * @throws \Exception
     */
    public function getSiteDatabase($localSiteName)
    {
        // Extract sites list.
        $sites = $this->getLocalSites($localSiteName);

        foreach ($sites as $domain => $folder) {
            if (!$pathToSettingsLocal = $this->getPathToLocalSettings($localSiteName)) {
              throw new FileNotFoundException(null, 0, null, $pathToSettingsLocal);
            }

            require_once $pathToSettingsLocal;
            if (!isset($conf) || empty($conf)) {
                throw new \Exception(sprintf('Looks like %s is corrupted. $conf variable not defined.', $pathToSettingsLocal));
            }

            if (!isset($conf['local_env_canonical_database_name']) || empty($conf['local_env_canonical_database_name'])) {
                $message = sprintf('Could not detect canonical database name. Please check settings.local.php file. Variable $conf["local_env_canonical_database_name"] should be defined.', $pathToSettingsLocal);
                throw new \Exception($message);
            }

            return $conf['local_env_canonical_database_name'];
        }

        return null;
    }

    /**
     * @param string $localSiteName
     *
     * @return string
     */
    public function getPathToLocalSite($localSiteName)
    {
        $pathToSite = sprintf(Workspace::CLUSTER_DIR.'/'.$localSiteName);
        if (false == $this->filesystem->exists($pathToSite)) {
            throw new FileNotFoundException(null, 0, null, $pathToSite);
        }

        return $pathToSite;
    }

    /**
     * @param string $localSiteName
     *
     * @return string
     */
    public function getPathToLocalSitesFile($localSiteName)
    {
        $path = sprintf('%s/docroot/sites/sites.local.php', $this->getPathToLocalSite($localSiteName));
        if (false == $this->filesystem->exists($path)) {
            throw new FileNotFoundException(null, 0, null, $path);
        }

        return $path;
    }

    /**
     * @param $localSiteName
     *
     * @return array
     * @throws \Exception
     */
    public function getLocalSitesFolders($localSiteName)
    {
        $items = $this->getLocalSites($localSiteName);

        return array_values($items);
    }

    /**
     * @param $localSiteName
     *
     * @return mixed
     * @throws \Exception
     */
    private function getLocalSites($localSiteName)
    {
        $sitesLocalFile = $this->getPathToLocalSitesFile($localSiteName);

        include $sitesLocalFile;

        if (!isset($sites)) {
            throw new \Exception(sprintf('Looks like %s is corrupted. $sites variable not defined.', $sitesLocalFile));
        }
        if (empty($sites)) {

            throw new \Exception(sprintf('Looks like %s is corrupted. $sites variable is empty.', $sitesLocalFile));
        }

        return $sites;
    }

    /**
     * Get the path to settings.local.php file.
     *
     * @param string $dirName
     *   The site's directory name.
     * @param string $clusterPath
     *   The path to a cluster directory.
     *
     * @return string|null
     *   The path to a file if it exists, otherwise - null.
     */
    public function getPathToLocalSettings($dirName, $clusterPath = Workspace::CLUSTER_DIR)
    {
        $siteName = $this->extractSiteIdFromDir($dirName, true);
        $path = sprintf('%s/%s/docroot/sites/%s/settings.local.php', $clusterPath, $dirName, $siteName);

        return $this->filesystem->exists($path) ? $path : null;
    }

    /**
     * Builds an absolute path to local site.
     *
     * @param string $dirName
     *   The site's directory name.
     * @pram boolean $platformOnly
     *   Path to a platform if TRUE, otherwise - FALSE.
     * @param string $clusterPath
     *   The path to a cluster directory.
     *
     * @return string
     *   The absolute path to a local site.
     */
    public function buildSiteAbsolutePath($dirName, $platformOnly = true, $clusterPath = Workspace::CLUSTER_DIR)
    {
        $platformPath = $clusterPath . '/' . $dirName;
        $sitePath = implode('/', [$platformPath, 'docroot/sites', $this->extractSiteIdFromDir($dirName, true)]);

        return $platformOnly ? $platformPath : $sitePath;
    }

    /**
     * Get the site domains from sites.local.php file.
     *
     * This file contains all actual domains for a site and this is the best
     * place to get such information.
     *
     * @param string $siteDir
     *   The path to a site.
     *
     * @return array
     *   The list of all available domains.
     */
    public function getDomains($siteDir)
    {
        $domains = [];
        $sitesLocal = implode('/', [realpath($siteDir), 'docroot/sites/sites.local.php']);

        // Read sites.local.php and get all available domains.
        if (file_exists($sitesLocal)) {
            $regexp = '/\$sites\[\'(?<domain>.+?)\'\]/';
            preg_match_all($regexp, file_get_contents($sitesLocal), $matches, PREG_PATTERN_ORDER);
            $domains = $matches['domain'] ? $matches['domain'] : [];
        }

        return $domains;
    }

    /**
     * Builds the platform version.
     *
     * @param string $path
     *   Path to a platform directory.
     *
     * @return string
     *   The platform version.
     */
    public function buildVersion($path)
    {
        // Try to fetch a platform version from the git.
        if (!$version = $this->git->getPlatformVersion($path)) {
          return '';
        }

        return sprintf('%s: %s', $version['type'], $version['version']);
    }

    /**
     * Extract the site ID from a site's directory name.
     *
     * @param string $dirName
     *   The site's directory name (listerine-us_1).
     * @param boolean $machineName
     *   Convert a site ID to machine representation or not.
     *
     * @return string
     *   The site ID.
     */
    public function extractSiteIdFromDir($dirName, $machineName = false)
    {
        $siteId = strstr($dirName, self::DIR_SEP_DEFAULT, true);
        return $machineName ? str_replace('-', '_', $siteId) : $siteId;
    }
}
