<?php

namespace App\Command\Platform;

use App\Command\AbstractCommand;
use App\Services\PlatformManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetPlatformCommand
 * @package AppBundle\Command
 */
class PlatformUpdateCommand extends AbstractCommand
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
          ->setName('app:platform:update')
          ->setDescription('Update JJBOS Platform.');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Updating JJBOS platform');
        $this->platformManager->update();
    }
}
