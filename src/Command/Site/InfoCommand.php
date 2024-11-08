<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Services\SiteInfoExtractor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class InfoCommand
 *
 * @package AppBundle\Command\Site
 */
class InfoCommand extends AbstractCommand
{

    /** @var \App\Services\SiteInfoExtractor */
    private $siteInfoExtractor;

    /** {@inheritdoc} */
    public function __construct(
      $name,
      SiteInfoExtractor $siteInfoExtractor
    ) {
        if (is_null($name)) {
            $name = NULL;
        }
        $this->siteInfoExtractor = $siteInfoExtractor;

        parent::__construct($name = null);
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
          ->setName('app:site:info')
          ->setDescription('Retrieve site info')
          ->addOption('url', null, InputOption::VALUE_REQUIRED);
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $siteUrl = $input->getOption('url');
        $siteUrl = trim($siteUrl, '/');
        $io->title(
          sprintf(
            'Try to detect site ID and platform version for site %s',
            $siteUrl
          )
        );

        try {
            $monitorDataInfo = $this->siteInfoExtractor->getMonitorInfo($siteUrl);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        if (empty($monitorDataInfo['hostname'])) {
            $this->stopExecution('Could not retrieve host name');
        }

        if (empty($monitorDataInfo['site'])) {
            $this->stopExecution('Could not retrieve subscription name');
        }

        $io->success(sprintf('Hostname: %s', $monitorDataInfo['hostname']));
        $io->success(sprintf('Subscription: %s', $monitorDataInfo['site']));

        try{
            $serverInfo = $this->siteInfoExtractor->getServerInfo($siteUrl);
        }catch (\Exception $exception){
            $io->error($exception->getMessage());
            exit;
        }

        $io->success(sprintf('Environment: %s', $serverInfo->getEnvironment()));
        $io->success(
          sprintf('Platform version: %s, tag: %s', $serverInfo->getPlatformVersion(), $serverInfo->getPlatformTag())
        );
        $io->success(sprintf('Site ID: %s', $serverInfo->getSiteId()));
    }

}
