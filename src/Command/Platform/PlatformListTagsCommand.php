<?php

namespace App\Command\Platform;

use App\Command\AbstractCommand;
use App\Services\PlatformManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetPlatformCommand
 * @package AppBundle\Command
 */
class PlatformListTagsCommand extends AbstractCommand
{
    /** @var PlatformManager */
    private $platformManager;

    /** {@inheritdoc} */
    public function __construct($name, PlatformManager $platformManager)
    {
        if (is_null($name)) {
            $name = NULL;
        }
        $this->platformManager = $platformManager;

        parent::__construct($name);
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('app:platform:list-tags')
            ->setDescription('List available tags.')
            ->addArgument('length', InputArgument::OPTIONAL, 'How many tags should be displayed.');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tags = $this->platformManager->getTagsList();

        $length = intval($input->getArgument('length'));

        if (!empty($length)) {
            $tags = array_reverse($tags);
            $tags = array_slice($tags, 0, $length);
        }

        $this->io->title('Tags list');
        $this->io->writeln($tags);
    }
}
