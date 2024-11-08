<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Event\WhitesiteInstall;
use App\Model\WhitesiteServerInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Process\CheckAccessMethodTrait;

/**
 * Represents the whitesite installation command.
 */
class WhitesiteInstallCommand extends AbstractCommand
{

    use CheckAccessMethodTrait;

    /**
     * The event dispatcher.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    public function __construct($name, EventDispatcherInterface $eventDispatcher)
    {
        if (is_null($name)) {
            $name = NULL;
        }
        parent::__construct($name);

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:whitesite:install')
            ->setDescription('Install whitesite')
            ->addOption('repository-url', 'r', InputOption::VALUE_REQUIRED)
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        // Validate the required input values.
        foreach (['repository-url', 'branch'] as $value) {
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
        try {
            $siteInstallEvent = new WhitesiteInstall(
                new WhitesiteServerInfo('master', 'whitesite'),
                $input->getOption('repository-url'),
                'master',
                $input->getOption('branch')
            );

            $phases = [
                WhitesiteInstall::PHASE_BUILD_PLATFORM,
                WhitesiteInstall::PHASE_BUILD_WHITESITE,
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
