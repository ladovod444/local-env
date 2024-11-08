<?php

namespace App\EventListener\SiteInstall;

use App\Event\SiteInstall;
use App\Model\ConsoleCommand;
use App\Services\LocalSiteCreator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use App\Services\LocalDomainBuilder;

/**
 * Class SitePostInstallDrushCommands
 *
 * @package AppBundle\EventListener
 */
class PostInstallDrushCommands extends AbstractSiteInstallListener
{

    /**
     * @var \App\Services\LocalSiteCreator;
     */
    private $siteCreator;

    /**
     * @var \App\Services\LocalDomainBuilder;
     */
    private $domainBuilder;

    /**
     * Constructs a PostInstallDrushCommands object.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \App\Services\LocalSiteCreator
     * @param \App\Services\LocalDomainBuilder
     */
    public function __construct(LoggerInterface $logger, LocalSiteCreator $siteCreator, LocalDomainBuilder $domainBuilder)
    {
        parent::__construct($logger);

        $this->siteCreator = $siteCreator;
        $this->domainBuilder = $domainBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEvent(SiteInstall $siteInstallEvent)
    {
        $siteId = $siteInstallEvent->getSiteServerInfo()->getSiteId();
        $cell = $siteInstallEvent->getVacantCell();
        $pathToSite = $this->siteCreator->getSiteInDocrootDirectory($siteId, $cell);
        $masterSite = $siteInstallEvent->getMasterSite();

        $drushCommands = Yaml::parse(file_get_contents('post-install.drush-commands.yml'));

        $domains = [];
        $langs = $this->siteGetLangs($pathToSite);

        // Set local domains for multilingual sites.
        if (count($langs) > 1) {
          foreach ($langs as $lang) {
            if (trim(shell_exec("(cd " . $pathToSite . " && drush sql-query \"SELECT domain FROM languages WHERE language='{$lang}';\")"))) {
              $domain = $this->domainBuilder->buildDomainName($siteId, $cell, $lang);
              array_unshift($drushCommands['commands'], "drush sql-query \"UPDATE languages SET domain='{$domain}' WHERE language='{$lang}';\"");
              $domains[] = $domain;
            }
          }
          // Override the sites.local.php file with necessary domains.
          $this->siteCreator->createSitesLocalFile($domains);
        }

        $this->logger->notice(sprintf('The following commands will be executed: %s', implode(', ', $drushCommands['commands'])));
        array_unshift($drushCommands['commands'], sprintf('cd %s', $pathToSite));
        array_push($drushCommands['commands'], 'cd --');

        $tokens = [
          '[site_dir]' => $siteId,
          '[master_site]' => $masterSite,
        ];

        foreach ($tokens as $token => $replacement) {
          $drushCommands['commands'] = preg_replace('/' . preg_quote($token) . '/', $replacement, $drushCommands['commands']);
        }

        $cmd = implode(' && ', $drushCommands['commands']);
        $process = new Process($cmd);
        $process->setTimeout(ConsoleCommand::PROCESS_TIMEOUT);
        $process->run($this->getRealTimeProcessCallback());
    }

    /**
     * Helper function to get sites languages.
     *
     * @param string $sitePath
     *   The path to a site.
     *
     * @return array
     *   The list of active languages.
     */
    protected function siteGetLangs($sitePath)
    {
        $commands = [
          sprintf('cd %s', $sitePath),
          'drush sql-query "SELECT language FROM languages WHERE enabled=1;"'
        ];

        // The only way to get a list of available languages is to perform a
        // request to appropriate database.
        $process = new Process(implode(' && ', $commands));
        $process->setTimeout(ConsoleCommand::PROCESS_TIMEOUT);
        $process->run();
        $output = $process->getOutput();

        preg_match_all('/(?<langs>.+?)\n/', $output, $matches);
        return isset($matches['langs']) ? $matches['langs'] : [];
    }
}
