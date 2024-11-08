<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Services\DatabaseInfoExtractor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class DownloadDatabase
 *
 * @package App\Command\Site
 */
class DownloadDatabaseCommand extends AbstractCommand
{

    /** @var DatabaseInfoExtractor */
    private $databaseInfoExtractor;

    /** {@inheritdoc} */
    public function __construct($name, DatabaseInfoExtractor $databaseInfoExtractor)
    {
        if (is_null($name)) {
            $name = NULL;
        }
        parent::__construct($name = null);

        $this->databaseInfoExtractor = $databaseInfoExtractor;
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('app:site:download:database')
            ->setDescription('Download the latest database dump.')
            ->addOption('url', null, InputOption::VALUE_REQUIRED);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteUrl = $input->getOption('url');
        $siteUrl = trim($siteUrl, '/');
        $this->io->title(sprintf('Trying to find the latest database backup for site %s', $siteUrl));

        try {
            $filename = $this->databaseInfoExtractor->saveDatabaseDump($siteUrl);

            $this->io->success(sprintf('Database dump saved to %s', realpath($filename)));
            $this->io->success(sprintf('File size: %s', $this->humanFileSize(filesize($filename))));
        } catch (\Exception $exception) {
            $this->stopExecution($exception->getMessage());
        }
    }

    private function humanFileSize($bytes, $decimals = 3)
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        $d = $bytes / pow(1024, $factor);
        $f = !empty($size[$factor]) ? $size[$factor] : '';

        return sprintf("%.{$decimals}f %s", $d, $f);
    }
}
