<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Model\ConsoleCommand;
use App\Process\ProcessAwareTrait;
use App\Services\DatabaseInfoExtractor;
use App\Services\LocalSiteManager;
use App\Services\SiteInfoExtractor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class DatabaseSync
 *
 * @package AppBundle\Command
 */
class SyncDatabaseCommand extends AbstractCommand
{

    use ProcessAwareTrait;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var SiteInfoExtractor
     */
    private $siteInfoExtractor;

    /**
     * @var DatabaseInfoExtractor
     */
    private $databaseInfoExtractor;

    /**
     * The local site manager.
     *
     * @var \App\Services\LocalSiteManager
     */
    private $LocalSiteManager;

    /**
     * DatabaseSyncCommand constructor.
     * @param null $name
     * @param LoggerInterface $logger
     * @param SiteInfoExtractor $siteInfoExtractor
     * @param DatabaseInfoExtractor $databaseInfoExtractor
     * @param LocalSiteManager $LocalSiteManager
     */
    public function __construct(
        $name,
        LoggerInterface $logger,
        SiteInfoExtractor $siteInfoExtractor,
        DatabaseInfoExtractor $databaseInfoExtractor,
        LocalSiteManager $LocalSiteManager
    ) {
        if (is_null($name)) {
            $name = NULL;
        }
        parent::__construct($name);

        $this->logger = $logger;
        $this->siteInfoExtractor = $siteInfoExtractor;
        $this->databaseInfoExtractor = $databaseInfoExtractor;
        $this->LocalSiteManager = $LocalSiteManager;
    }

    /** {@inheritdoc} */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $source = $input->getOption('source-url');
        if (empty($source)) {
            $this->stopExecution('--source-url option is required');
        }

        $target = $input->getOption('target');
        if (empty($target)) {
            $this->stopExecution('--target option is required');
        }
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('app:site:database-sync')
            ->setDescription('Sync database from target site to local')
            ->addOption('source-url', 's', InputOption::VALUE_REQUIRED, 'Source site URL')
            ->addOption('target', 't', InputOption::VALUE_REQUIRED, 'Local site id');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getOption('source-url');
        $target = $input->getOption('target');

        $this->io->section(sprintf('Starting database sync from %s to %s.', $source, $target));

        try {
            $pathToSite = $this->LocalSiteManager->buildSiteAbsolutePath($target, false);

            // @TODO Check database existence.

            $pathToBackup = $this->databaseInfoExtractor->saveDatabaseDump($source);

            $commands =                 [
                sprintf('cd %s', $pathToSite),
                sprintf('drush st'),
                sprintf('drush sql-drop -y --verbose'),
                sprintf('drush sql-query --file=%s --verbose', realpath($pathToBackup)),
                'cd --',
            ];

            (new Process(implode(' && ', $commands)))
                ->setTimeout(ConsoleCommand::PROCESS_TIMEOUT)
                ->run($this->getRealTimeProcessCallback());

            $this->io->title('Database has been synced successfully.');

        } catch (\Exception $exception) {
            $this->stopExecution($exception->getMessage());
        }
    }
}
