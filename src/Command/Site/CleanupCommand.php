<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Services\EnvironmentCleaner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanupCommand
 *
 * @package AppBundle\Command\Site
 */
class CleanupCommand extends AbstractCommand
{

    /**
     * @var EnvironmentCleaner
     */
    private $environmentCleaner;

    /**
     * CleanupCommand constructor.
     *
     * @param null               $name
     * @param EnvironmentCleaner $environmentCleaner
     */
    public function __construct($name, EnvironmentCleaner $environmentCleaner)
    {
        if (is_null($name)) {
            $name = NULL;
        }
        parent::__construct($name);

        $this->environmentCleaner = $environmentCleaner;
    }

    /** {@inheritdoc} */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $options = $input->getOptions();
        if (empty($options['id'])) {
            $this->stopExecution('--id option is required.');
        }
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('app:site:cleanup')
            ->setDescription('Cleanup environment')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Local site folder name.');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Cleaning up environment');

        $this->environmentCleaner->cleanup($input->getOption('id') );
    }
}
