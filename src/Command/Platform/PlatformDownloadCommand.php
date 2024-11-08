<?php

namespace App\Command\Platform;

use App\Command\AbstractCommand;
use App\Model\Workspace;
use App\Services\PlatformManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetPlatformCommand
 * @package AppBundle\Command
 */
class PlatformDownloadCommand extends AbstractCommand
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
            ->setName('app:platform:download')
            ->setDescription('Clones JJBOS Platform locally!.');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Downloading JJBOS platform');
        $this->platformManager->clonePlatform();
        $this->io->success(sprintf('Platform has been cloned to %s', Workspace::PLATFORM_DIR));
    }
}
