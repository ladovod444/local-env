<?php

namespace App\EventListener\SiteInstall;

use App\Event\SiteInstall;
use App\Exception\ManagerException;
use App\Model\Workspace;
use App\Services\GitDriver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Services\LocalSiteCreator;
use App\Services\DataProviderBridge;
use App\Process\SshCheckTrait;

/**
 * Represents the build platform event.
 */
class BuildPlatform extends AbstractSiteInstallListener
{

    use SshCheckTrait;

    /**
     * Provides basic utility to manipulate the file system.
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * The git driver.
     *
     * @var \App\Services\GitDriver
     */
    private $git;

    /**
     * The local site creator.
     *
     * @var \App\Services\LocalSiteCreator
     */
    private $siteCreator;

    /**
     * The data provider.
     *
     * @var \App\Services\DataProviderBridge
     */
    private $dataProvider;

    /**
     * {@inheritdoc}
     */
    public function __construct(LoggerInterface $logger, Filesystem $filesystem, GitDriver $gitDriver, LocalSiteCreator $siteCreator, DataProviderBridge $dataProvider) {
        parent::__construct($logger);
        $this->dataProvider = $dataProvider;
        $this->filesystem = $filesystem;
        $this->git = $gitDriver;
        $this->siteCreator = $siteCreator;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEvent(SiteInstall $siteInstallEvent)
    {
        if ($siteInstallEvent->getSiteServerInfo() instanceof \App\Model\SiteServerInfo) {
            $environmentSettings = $this->dataProvider->getEnvironmentPropertyByHostName($siteInstallEvent->getSiteServerInfo()->getSiteUrl(), 'settings');
            $user = $environmentSettings->remote_user;
            $host = $environmentSettings->remote_host;

            if (!$this->checkSshConnection($user, $host)) {
                $this->logger->error('Acquia: Public Key Authentication Failed');
                exit;
            }
        }

        $defVersion = $siteInstallEvent->getSiteServerInfo()->getDefaultPlatformVersion();
        $targetDir = $this->getTargetDirectory(
            $siteInstallEvent->getTergetSiteId(),
            $siteInstallEvent->getVacantCell()
        );

        $this->logger->notice('Sync platform to a target folder');
        $this->filesystem->mirror(Workspace::PLATFORM_DIR, $targetDir);

        // Checkout to a specific platform version if it has been specified,
        // otherwise - default version will be used.
        $gitWrapper = $this->git->getWrapper();
        $version = $siteInstallEvent->getplatformVersion();
        $version = $version ? $version : $defVersion;
        $this->logger->notice(sprintf('Checking out platform version: %s', $version));

        try {
            $gitWrapper->workingCopy($targetDir)->checkout($version);
        }
        catch (\Exception $e) {
            throw new ManagerException($e->getMessage(), $e->getCode(), $e, $targetDir);
        }
    }

    /**
     * Helper function to get target directory.
     *
     * @param string $siteId
     *   The site ID.
     * @param int $vacantCell
     *   The vacant cell.
     *
     * @return string
     *   The target directory.
     */
    public function getTargetDirectory($siteId, $vacantCell) {
      return Workspace::CLUSTER_DIR .'/' . $siteId . '_' . $vacantCell;
    }
}
