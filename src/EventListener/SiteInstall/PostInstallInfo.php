<?php

namespace App\EventListener\SiteInstall;

use App\Event\SiteInstall;
use App\Model\Workspace;
use App\Services\LocalSiteCreator;
use App\Services\LocalSiteManager;
use Psr\Log\LoggerInterface;

/**
 * Represents the post install event.
 */
class PostInstallInfo extends AbstractSiteInstallListener
{
    /**
     * The local site creator.
     *
     * @var \App\Services\LocalSiteCreator
     */
    private $siteCreator;

    /**
     * The local site manager.
     *
     * @var \App\Services\LocalSiteManager
     */
    private $localSiteManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(LoggerInterface $logger, LocalSiteCreator $siteCreator, LocalSiteManager $siteManager)
    {
        parent::__construct($logger);

        $this->siteCreator = $siteCreator;
        $this->localSiteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEvent(SiteInstall $siteInstallEvent)
    {
        $siteId = $siteInstallEvent->getSiteServerInfo()->getSiteId();
        $cell = $siteInstallEvent->getVacantCell();
        $pathToSite = $this->siteCreator->getSiteInDocrootDirectory($siteId, $cell);

        $path = Workspace::CLUSTER_DIR . '/' . $this->siteCreator->getUniqueId($siteId, $cell);
        $domains = $this->localSiteManager->getDomains($path);

        $this->logger->notice('Site has been deployed to local environment.');
        $this->logger->notice('Add following lines to your hosts file:');
        $ip = trim(shell_exec("/sbin/ifconfig eth1 | grep \"inet \" | awk '{gsub(\"addr:\",\"\",$2);  print $2 }'"));
        foreach ($domains as $domain) {
          $this->logger->notice(sprintf('%s %s', $ip, $domain));
        }

        $this->logger->notice(sprintf('Site will be accessible by following urls:'));
        foreach ($domains as $domain) {
          $this->logger->notice(sprintf('http://%s', $domain));
          $this->logger->notice(sprintf('https://%s', $domain));
        }

        $this->logger->notice('Site info');
        $this->logger->notice(sprintf('Path to site %s', $pathToSite));
        $this->logger->notice(sprintf('Files directory %s/files', $pathToSite));
    }
}
