<?php

namespace App\Command\Site;

use App\Command\AbstractCommand;
use App\Event\NewCloneInstall;
use App\Model\NewCloneServerInfo;
use App\Services\PlatformManager;
use App\Model\NewClone;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Process\CheckAccessMethodTrait;

/**
 * Class NewCloneInstallCommand
 *
 * @package AppBundle\Command\Site
 */
class NewCloneInstallCommand extends AbstractCommand
{

    use CheckAccessMethodTrait;

    /** @var PlatformManager */
    private $platformManager;

    /** @var EventDispatcher */
    private $eventDispatcher;

    /** {@inheritdoc} */
    public function __construct(
        $name,
        EventDispatcherInterface $eventDispatcher,
        PlatformManager $platformManager
    ) {
        if (is_null($name)) {
            $name = NULL;
        }
        parent::__construct($name);

        $this->eventDispatcher = $eventDispatcher;
        $this->platformManager = $platformManager;
    }

    /** {@inheritdoc} */
    public function configure()
    {
        $this
            ->setName('app:newclone:install')
            ->setDescription('Install new clone')
            ->addOption('repository-url', 'r', InputOption::VALUE_REQUIRED)
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED)
            ->addOption('clone-name', 'cn', InputOption::VALUE_REQUIRED)
            ->addOption('master-name', 'mn', InputOption::VALUE_REQUIRED)
            ->setHelp('Creates a new theme for the clone. Example: php ./bin/console app:newclone:install --repository-url="Git repository" --branch="Git branch" --clone-name="Clone name" --master-name="Master name"');
    }

    /** {@inheritdoc} */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        // Validate the required input values.
        foreach (['repository-url', 'branch', 'clone-name', 'master-name'] as $value) {
            if (empty($input->getOption($value))) {
                $this->stopExecution("--{$value} option is required.");
            }
        }
        if ($this->isSshUsed($input->getOption('repository-url'))) {
            $this->stopExecution("Wrong access method. Please use HTTPS to access git repository.");
        }
    }

    /** {@inheritdoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Preparing directory structure.');

        try {
            $repositoryUrl = $input->getOption('repository-url');
            $branch = $input->getOption('branch');
            $cloneName = $input->getOption('clone-name');
            $masterName = $input->getOption('master-name');

            $availableTags = $this->platformManager->getTagsList();
            $tags = array_reverse($availableTags);
            $tags = array_slice($tags, 0, 5);

            $platformTag = $this->io->choice('Select tag', $tags, $tags[0]);

            $newCloneInstallEvent = new NewCloneInstall(
                new NewCloneServerInfo($platformTag, $cloneName),
                $repositoryUrl,
                $branch,
                $platformTag,
                $masterName
            );

            $phases = [
                NewCloneInstall::PHASE_BUILD_PLATFORM,
                NewCloneInstall::PHASE_BUILD_SITE,
            ];
            foreach ($phases as $phase) {
                $this->eventDispatcher->dispatch($phase, $newCloneInstallEvent);
            }
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $this->stopExecution($message);
        }
    }
}
