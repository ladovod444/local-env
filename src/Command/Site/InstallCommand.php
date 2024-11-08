<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Event\CloneInstall;
use App\Model\SiteServerInfo;
use App\Services\SiteInfoExtractor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Process\CheckAccessMethodTrait;

/**
 * Represents the installation command.
 */
class InstallCommand extends AbstractCommand
{

    use CheckAccessMethodTrait;

    /**
     * The site info extractor.
     *
     * @var \App\Services\SiteInfoExtractor
     */
    private $siteInfoExtractor;

    /**
     * The event dispatcher.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    public function __construct($name, SiteInfoExtractor $infoExtractor, EventDispatcherInterface $eventDispatcher)
    {
        if (is_null($name)) {
            $name = NULL;
        }
        parent::__construct($name);

        $this->eventDispatcher = $eventDispatcher;
        $this->siteInfoExtractor = $infoExtractor;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:site:install')
            ->setDescription('Install site')
            ->addOption('url', 'u', InputOption::VALUE_REQUIRED)
            ->addOption('repository-url', 'r', InputOption::VALUE_REQUIRED)
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED)
            ->addOption('platform-version', 'p', InputOption::VALUE_OPTIONAL)
            // An extra option to allow specify a theme name in unpredictable
            // situations. It is hidden by default.
            ->addOption('theme', 't', InputOption::VALUE_OPTIONAL);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        // Validate the required input values.
        foreach (['url', 'repository-url', 'branch'] as $value) {
            if (empty($input->getOption($value))) {
                $this->stopExecution("--{$value} option is required.");
            }
        }
        if ($this->isSshUsed($input->getOption('repository-url'))) {
            $this->stopExecution("Wrong access method. Please use HTTPS to access git repository.");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteUrl = $input->getOption('url');
        $siteUrl = trim($siteUrl, '/');


        try {

            //dump($this->siteInfoExtractor->getServerInfo($siteUrl, $input->getOption('theme')));die();

          $ServerInfo = new SiteServerInfo(
            $siteUrl,
            'test_env',
            '2.22',
            'acuvue_id',
            'acuvue_id'
          );

            $siteInstallEvent = new CloneInstall(
                $this->siteInfoExtractor->getServerInfo($siteUrl, $input->getOption('theme')),
                //$ServerInfo,
                $input->getOption('repository-url'),
                $input->getOption('branch'),
                $input->getOption('platform-version')
            );

            $phases = [
                CloneInstall::PHASE_BUILD_PLATFORM,
                CloneInstall::PHASE_BUILD_SITE,
                CloneInstall::PHASE_DOWNLOAD_DATABASE,
                CloneInstall::PHASE_DOWNLOAD_FILES,
                CloneInstall::PHASE_POST_PUBLISH,
            ];
            foreach ($phases as $phase) {
                $this->eventDispatcher->dispatch($phase, $siteInstallEvent);
            }

        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $this->stopExecution($message);
        }
    }
}
