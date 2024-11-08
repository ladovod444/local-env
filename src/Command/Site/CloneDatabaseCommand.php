<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Services\DatabaseManager;
use App\Services\LocalSiteManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CloneDatabaseCommand
 * @package AppBundle\Command
 */
class CloneDatabaseCommand extends AbstractCommand
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var LocalSiteManager
     */
    private $localSiteManager;

    /**
     * CloneDatabaseCommand constructor.
     *
     * @param null $name
     * @param DatabaseManager $databaseManager
     * @param LocalSiteManager $localSiteManager
     */
    public function __construct($name, DatabaseManager $databaseManager, LocalSiteManager $localSiteManager)
    {
        if (is_null($name)) {
            $name = NULL;
        }
        parent::__construct($name);

        $this->databaseManager = $databaseManager;
        $this->localSiteManager = $localSiteManager;
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('app:site:database-clone')
            ->setDescription('Clone database for current site')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Local site folder name.');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localSiteName = $input->getOption('id');

        $this->io->title(sprintf('Cloning database "%s"', $localSiteName));
        $databaseName = $this->localSiteManager->getSiteDatabase($localSiteName);
        $newDatabaseName = $this->databaseManager->makeClone($databaseName);
        $message = sprintf('Database has been copied form %s to %s. ', $databaseName, $newDatabaseName);
        $this->io->success($message);

        $pathToSettings = $this->localSiteManager->getPathToLocalSettings($localSiteName);
        $this->io->comment(sprintf('Please update %s', $pathToSettings));
    }
}
