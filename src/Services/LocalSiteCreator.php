<?php

/**
 * @file
 */

namespace App\Services;

use App\Exception\ManagerException;
use App\Model\Whitesite;
use App\Model\Workspace;
use App\Model\NewClone;
use App\Process\ProcessAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class LocalSiteCreator
 *
 * @package AppBundle\Services
 */
class LocalSiteCreator
{

    use ProcessAwareTrait;

    /** @var string */
    private $siteId;

    /** @var string */
    private $siteKey;

    /** @var LoggerInterface */
    private $logger;

    /** @var Filesystem */
    private $fileSystem;

    /** @var GitDriver */
    private $git;

    /** @var string */
    private $platformTag;

    /** @var string */
    private $dbUser;

    /** @var string */
    private $dbPassword;

    /** @var LocalDomainBuilder */
    private $domainBuilder;
    /**
     * @var SiteThemeCompiler
     */
    private $themeCompiler;

    /**
     * LocalSiteCreator constructor.
     *
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param GitDriver $gitDriver
     * @param LocalDomainBuilder $domainBuilder
     * @param SiteThemeCompiler $themeCompiler
     * @param $dbUser
     * @param $dbPassword
     */
    public function __construct(
        LoggerInterface $logger,
        Filesystem $filesystem,
        GitDriver $gitDriver,
        LocalDomainBuilder $domainBuilder,
        SiteThemeCompiler $themeCompiler,
        $dbUser,
        $dbPassword
    ) {
        $this->logger = $logger;
        $this->fileSystem = $filesystem;
        $this->git = $gitDriver;
        $this->domainBuilder = $domainBuilder;

        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
        $this->themeCompiler = $themeCompiler;
    }

    /**
     * @param string $siteId
     * @param string $repositoryUrl
     * @param string $branch
     * @param string $cell
     * @param string $theme
     */
    public function createSite($siteId, $repositoryUrl, $branch, $cell, $theme = '')
    {
        $this->init($siteId, $cell)
            ->createSiteDir()
            ->cloneSite($repositoryUrl, $branch)
            ->createSettings()
            ->createFilesFolder()
            ->linkSite()
            ->compileTheme($theme);
    }

    public function createWhitesite($repositoryUrl, $branch, $cell)
    {
        $this->init(Whitesite::PLATFORM_IDENTIFIER, $cell)
            ->createSiteDir()
            ->cloneSite($repositoryUrl, $branch)
            ->createSettings()
            ->createFilesFolder()
            ->linkSite()
            ->compileTheme($this->getWhitesiteTheme($repositoryUrl));
    }

    /**
     * Get whitesite theme using repository url.
     *
     * @param string $repositoryUrl
     *   The repository url.
     *
     * @return string
     *   The theme name.
     */
    public function getWhitesiteTheme($repositoryUrl)
    {
        // By default, we use sitename in order to detect a theme name. And it
        // works fine for brand sites, since in most cases theme name and site
        // name (git repository) are equal. Unfortunately, this principle does
        // not work for a whitesite. Use this trick to support site1, site2 and
        // similar themes.
        // @see LocalSiteCreator::compileTheme()
        // @todo Find another solution here!
        $themePrefix = 'jjbos';

        $pos = stripos($repositoryUrl, $themePrefix) + strlen($themePrefix) + 1;
        return str_replace($themePrefix, '', substr($repositoryUrl, $pos, -4));
    }

    public function cloneTheme($cloneName, $masterName, $repositoryUrl, $branch, $cell)
    {
        $this->init($cloneName, $cell)
            ->createSiteDir()
            ->cloneSite($repositoryUrl, $branch)
            ->createSettings()
            ->createFilesFolder()
            ->linkSite()
            ->copyBrandTheme()
            ->compileBrandTheme();
        $pathToMasterTheme = $this->getPathToTheme($masterName);
        if (!file_exists($pathToMasterTheme)) {
            $this->siteInstall($masterName);
        }
        $this->siteInstall($masterName);
    }

    /**
     * @param $siteId
     * @param $platformTag
     *
     * @return string
     */
    public function getUniqueId($siteId, $platformTag)
    {
        $this->init($siteId, $platformTag);

        return sprintf('%s_%s', $this->siteKey, $this->platformTag);
    }

    /**
     * @param $siteId
     * @param $platformTag
     *
     * @return string
     */
    public function buildDatabaseName($siteId, $platformTag)
    {
        $this->init($siteId, $platformTag, '_');

        return sprintf('%s_%s', $this->siteId, $this->platformTag);
    }

    public function getSiteBaseDir($siteId, $platformTag)
    {
        return sprintf('%s/%s', Workspace::CLUSTER_DIR, $this->getUniqueId($siteId, $platformTag));
    }

    /**
     * @param $siteId
     * @param $platformTag
     *
     * @return string
     */
    public function getSiteDirectory($siteId, $platformTag)
    {
        $siteDirectory = sprintf(
            '%s/%s/sites/%s',
            Workspace::CLUSTER_DIR,
            $this->getUniqueId($siteId, $platformTag),
            $this->siteId
        );

        return $siteDirectory;
    }

    public function getSiteInDocrootDirectory($siteId, $platformTag)
    {
        $siteDirectory = sprintf(
            '%s/%s/docroot/sites/%s',
            Workspace::CLUSTER_DIR,
            $this->getUniqueId($siteId, $platformTag),
            $this->siteId
        );

        return $siteDirectory;
    }

    /**
     * @param string $siteId
     * @param string $platformTag
     * @param string $brandsite
     *
     * @return string
     */
    public function getBrandThemeInDocrootDirectory($siteId, $platformTag, $brandsite = '')
    {
        $siteDirectory = sprintf(
            '%s/%s/docroot/sites/%s/themes/brand_theme',
            Workspace::CLUSTER_DIR,
            $this->getUniqueId($siteId, $platformTag),
            $brandsite ?: $this->siteId
        );

        return $siteDirectory;
    }

    /**
     * @param string $siteId
     * @param string $platformTag
     * @param string $brandsite
     *
     * @return string
     */
    public function getBrandThemeSymlink($siteId = '')
    {
        $siteDirectory = sprintf(
            '%s/%s/themes/brand_theme',
            NewClone::PATH_FOR_SYMLINK,
            $siteId ?: $this->siteId
        );

        return $siteDirectory;
    }

    /**
     * @param string $siteId
     * @param string $platformTag
     * @param string $separator
     *
     * @return $this
     */
    private function init($siteId, $platformTag, $separator = '-')
    {
        $this->siteId = $siteId;
        $this->siteKey = str_replace('_', '-', $siteId);
        $this->platformTag = preg_replace(
            '/[^a-zA-Z0-9]+/i',
            $separator,
            $platformTag
        );

        return $this;
    }

    private function createSettings()
    {
        $this->logger->info('Building settings.local.php file.');
        $pathToTemplate = Workspace::FILE_TEMPLATES.'/site/settings.local.php';
        $targetPath = sprintf(
            '%s/src/settings.local.php',
            $this->getSiteDirectory($this->siteId, $this->platformTag)
        );

        $settingsFileContent = file_get_contents($pathToTemplate);
        $settingsFileContent = strtr(
            $settingsFileContent,
            [
                '%db_user%' => $this->dbUser,
                '%db_password%' => $this->dbPassword,
                '%database%' => $this->buildDatabaseName(
                    $this->siteId,
                    $this->platformTag
                ),
                '%memcache_key_prefix%' => $this->getUniqueId(
                        $this->siteId,
                        $this->platformTag
                    ).'_',
                '%file_public_path%' => $this->getFilesDir(),
            ]
        );

        $settingsFileContent .= PHP_EOL.PHP_EOL.sprintf('// Created at: %s', date('d.m.Y - h:i:s')).PHP_EOL;
        $this->fileSystem->dumpFile($targetPath, $settingsFileContent);

        return $this;
    }

    /**
     * @return $this
     */
    private function createFilesFolder()
    {
        $pathToFiles = sprintf('%s/src/files', $this->getSiteDirectory($this->siteId, $this->platformTag));

        if (false == $this->fileSystem->exists($pathToFiles)) {
            $this->fileSystem->mkdir($pathToFiles);
        }

        $this->fileSystem->chmod($pathToFiles, 0777);

        return $this;
    }

    private function changeNames($files, $oldName, $newName, $masterName, $newMaster = FALSE)
    {
        foreach ($files as $file) {
            $content = file_get_contents($file);

            if ($newMaster) {
                $fileContent = strtr(
                    $content,
                    [
                        NewClone::BRAND_THEME => $masterName,
                        $oldName => $newName,
                    ]
                );
            }
            else {
                $fileContent = strtr(
                    $content,
                    [
                        NewClone::BRAND_THEME => $this->siteId,
                        $oldName => $newName,
                        'base theme = ' . NewClone::PLATFORM_BASE_THEME => 'base theme = ' . $masterName,
                    ]
                );
            }
            $this->fileSystem->dumpFile($file, $fileContent);
        }
    }

    /**
     * @param $list array
     * @return $this
     */
    public function removeUnusedFiles($list)
    {
        foreach ($list as $removeFile) {
            $this->fileSystem->remove($removeFile);
        }

        return $this;
    }

    /**
     * @param $replacedFiles
     * @param $fileForReplace
     * @return $this
     */
    public function replaceToEmptyFile($replacedFiles, $fileForReplace)
    {
        foreach ($replacedFiles as $replacedFile) {
            if (stripos($fileForReplace, $replacedFile) !== false) {
                $emptyFile = str_replace($replacedFile, NewClone::EMPTY_FILE, $fileForReplace);
                $this->fileSystem->dumpFile($emptyFile, '');
                $this->fileSystem->remove($fileForReplace);
            }
        }

        return $this;
    }

    private function linkSite()
    {
        $originDir = sprintf('%s/src', $this->getSiteDirectory($this->siteId, $this->platformTag));

        $targetDir = sprintf(
            '%s/docroot/sites/%s',
            $this->getSiteBaseDir($this->siteId, $this->platformTag),
            $this->siteId
        );

        $this->fileSystem->symlink(realpath($originDir), $targetDir, true);

        return $this;
    }

    /**
     * @return string
     */
    private function getFilesDir()
    {
        return sprintf('sites/%s/files', $this->siteId);
    }

    /**
     * @return string
     */
    private function getPathToTheme($theme = '')
    {
        $pathToTheme = sprintf(
            '%s/docroot/sites/%s/themes/%s',
            $this->getSiteBaseDir($this->siteId, $this->platformTag),
            $this->siteId,
            $theme ?: $this->siteId
        );

        return $pathToTheme;
    }

    /**
     * @param string $theme
     *
     * @return $this
     */
    private function compileTheme($theme = '')
    {
        $this->logger->notice('Compiling site theme');

        $pathToTheme = $this->getPathToTheme($theme);
        $this->themeCompiler->installThemeAssets($pathToTheme);
        $this->themeCompiler->buildTheme($pathToTheme);

        return $this;
    }
		
    /**
     * @return $this
     */
    private function copyBrandTheme()
    {
        $this->logger->notice('Cloning brand_theme to the themes directory');

        $pathToBrandTheme = $this->getBrandThemeInDocrootDirectory($this->siteId, $this->platformTag, 'brandsite');
        $pathToThemes = sprintf(
            '%s/docroot/sites/%s/themes',
            $this->getSiteBaseDir($this->siteId, $this->platformTag),
            $this->siteId
        );
        $this->themeCompiler->copyBrandTheme($pathToBrandTheme, $pathToThemes, FALSE);

        return $this;
    }

    /**
     * @return $this
     */
    private function compileBrandTheme()
    {
        $this->logger->notice('Compiling brand_theme');
        $pathToBrandTheme = $this->getBrandThemeInDocrootDirectory($this->siteId, $this->platformTag);
        $this->themeCompiler->gfoTheme($pathToBrandTheme);

        return $this;
    }

    /**
     * @return $this
     */
    private function siteInstall($masterName)
    {
        $this->logger->notice('Running site install task');

        $newMaster = FALSE;
        $pathToBrandTheme = $this->getBrandThemeInDocrootDirectory($this->siteId, $this->platformTag);
        $pathToTheme = $this->getPathToTheme();
        $siteInfoName = $pathToTheme . '/' . $this->siteId . '.info';

        $toTitleCase = ucwords(str_replace('_', ' ', NewClone::BRAND_THEME));
        $toNewTitleCase = ucwords(str_replace('_', ' ', $this->siteId));

        $pathToMasterTheme = $this->getPathToTheme($masterName);
        if (!file_exists($pathToMasterTheme)) {
            $newMaster = TRUE;
            $pathToTheme = $pathToMasterTheme;
            $siteInfoName = $pathToTheme . '/' . $masterName . '.info';
            $toNewTitleCase = ucwords(str_replace('_', ' ', $masterName));
        }

        $message = sprintf(
            'Copy the compilation of brand_theme to the %s directory.',
            $pathToTheme
        );
        $this->logger->notice($message);
        $this->themeCompiler->copyBrandTheme($pathToBrandTheme . '/', $pathToTheme);

        // Rename .info file.
        if (file_exists($pathToTheme . '/' . NewClone::BRAND_THEME . '.info')) {
            $this->fileSystem->rename($pathToTheme . '/' . NewClone::BRAND_THEME . '.info', $siteInfoName);
        }

        // Rename brand theme to clone theme, for all content of files.
        foreach (NewClone::$themeNameLocates as $themeNameLocate) {
            $listThemeNameLocates = glob($pathToTheme . $themeNameLocate);
            if ($listThemeNameLocates) {
                if ($newMaster) {
                    $this->changeNames($listThemeNameLocates, $toTitleCase, $toNewTitleCase, $masterName, $newMaster);
                } else {
                    $this->changeNames($listThemeNameLocates, $toTitleCase, $toNewTitleCase, $masterName);
                }
            }
        }

        if (!$newMaster) {
            $this->logger->info('Building config.json file');
            $configJson = [
                'baseThemeName' => $masterName,
            ];
            $this->fileSystem->dumpFile($pathToTheme . '/config.json', json_encode($configJson));
            $this->fileSystem->remove($pathToBrandTheme);
        }

        $this->themeCompiler->updateSite($pathToTheme);
        $this->themeCompiler->gfoTheme($pathToTheme);

        $this->logger->info('Remove unused files');

        $stylesList = glob($pathToTheme . NewClone::DEBUG_STYLES_SASS);
        foreach (NewClone::$unusedFiles as $unusedFile) {
            $listFiles = glob($pathToTheme . $unusedFile);
            $this->removeUnusedFiles($listFiles);
        }

        foreach ($stylesList as $stylesFile) {
            $this->replaceToEmptyFile(NewClone::$replacedFiles, $stylesFile);
        }

        return $this;
    }

    /**
     * Prepare a site directory.
     *
     * @return self
     */
    private function createSiteDir()
    {
        $targetDir = $this->getSiteDirectory($this->siteId, $this->platformTag);

        // Create a site directory.
        $this->fileSystem->mkdir($targetDir);
        $this->logger->info(sprintf('Site directory has been created: %s', $targetDir));

        // Put a sites.local.php file.
        $domain = $this->domainBuilder->buildDomainName($this->siteId, $this->platformTag);
        $this->createSitesLocalFile([$domain]);
        $this->logger->info('The sites.local.php file has been built.');

        return $this;
    }

    /**
     * Clone a site and switch to a necessary branch.
     *
     * @param string $repositoryUrl
     *   A site repository.
     * @param string $branchName
     *   A branch to switch on.
     *
     * @return self
     * @throws \Exception
     */
    private function cloneSite($repositoryUrl, $branchName)
    {
        $targetDir = $this->getSiteDirectory($this->siteId, $this->platformTag);
        $baseDir = $this->getSiteBaseDir($this->siteId, $this->platformTag);

        try {
            $this->git->getRepositoryOrClone($targetDir, $repositoryUrl);
        } catch (\Exception $e) {
            throw new ManagerException($e->getMessage(), $e->getCode(), $e, $baseDir);
        }

        try {
            $this->git->switchBranch($targetDir, $branchName);
        } catch (\Exception $e) {
            throw new ManagerException($e->getMessage(), $e->getCode(), $e, $baseDir);
        }

        return $this;
    }

    /**
     * Creates sites.local.php file and writes available site domains.
     *
     * @param array $domains
     *   A list of domains we should write to a file.
     *
     * @todo Rid of sites.local.php template file!
     */
    public function createSitesLocalFile(array $domains)
    {
        // There are no available domains, so do nothing.
        if (!$domains) {
            return;
        }

        // All domains should be added to a file.
        $fileContent = '<?php' . PHP_EOL;
        foreach ($domains as $domain) {
            $fileContent .= sprintf("\$sites['%s'] = '%s';" . PHP_EOL, $domain, $this->siteId);
        }
        $fileContent .= sprintf('// Created at: %s', date('d.m.Y - h:i:s')) . PHP_EOL;

        $targetPath = sprintf('%s/docroot/sites/sites.local.php', $this->getSiteBaseDir($this->siteId, $this->platformTag));
        $this->fileSystem->dumpFile($targetPath, $fileContent);
    }
}
