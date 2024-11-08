<?php

namespace App\Command;

use AppBundle\AppBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Represents a command to get current app version.
 */
class VersionCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:version')
            ->setDescription('Get the application version.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Version: ' . AppBundle::VERSION);
    }
}
