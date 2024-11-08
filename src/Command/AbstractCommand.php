<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AbstractCommand
 *
 * @package AppBundle\Command
 */
abstract class AbstractCommand extends Command
{

    /** @var SymfonyStyle */
    protected $io;

    /** {@inheritdoc} */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Output error message and stop command execution.
     *
     * @param string $message
     */
    protected function stopExecution($message = '')
    {
        $this->io->error($message);
        exit;
    }
}
