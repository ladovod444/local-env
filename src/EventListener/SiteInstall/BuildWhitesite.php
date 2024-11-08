<?php

namespace App\EventListener\SiteInstall;

use App\Event\SiteInstall;
use App\Model\ConsoleCommand;
use App\Model\Whitesite;
use App\Model\Workspace;
use App\Services\LocalSiteCreator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Helps to build a whitesite.
 */
class BuildWhitesite extends AbstractSiteInstallListener
{
    /**
     * The local site creator.
     *
     * @var \App\Services\LocalSiteCreator
     */
    private $localSiteCreator;

    /**
     * {@inheritdoc}
     */
    public function __construct(LoggerInterface $logger, LocalSiteCreator $siteCreator) {
        parent::__construct($logger);
        $this->localSiteCreator = $siteCreator;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEvent(SiteInstall $siteInstallEvent)
    {
        $repository = $siteInstallEvent->getSiteRepository();
        $branch = $siteInstallEvent->getSiteBranch();
        $cell = $siteInstallEvent->getVacantCell();

        $this->logger->notice('Building site');
        $this->localSiteCreator->createWhitesite($repository, $branch, $cell);

        $id = $this->localSiteCreator->getUniqueId(Whitesite::PLATFORM_IDENTIFIER, $cell);
        $siteDir = sprintf('%s/%s', Workspace::CLUSTER_DIR, $id);
        $pathToSite = sprintf('%s/docroot/sites/%s', $siteDir, Whitesite::PLATFORM_IDENTIFIER);
        $theme = $this->localSiteCreator->getWhitesiteTheme($repository);

        $commands = [
            trim(Whitesite::installWhitesite($siteDir, $theme)),
            "drush pm-enable {$theme} -y",
            "drush vset theme_default {$theme}",
            'cd --'
        ];

        // The install.sh installation script can failure. So, use & between
        // commands to force running all commands, regardless of the success or
        // failure some of them (& can't be used after cd).
        $commandSeparator = '&';
        $cmd = sprintf('cd %s', $pathToSite) . '&&' . implode($commandSeparator, $commands);

        $process = new Process($cmd);
        $process->setTimeout(ConsoleCommand::PROCESS_TIMEOUT);
        $process->run($this->getRealTimeProcessCallback());
    }
}
