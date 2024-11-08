<?php

namespace App\Command;

use App\Model\VirtualMachine;
use App\Model\Workspace;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class InstallCommand
 *
 * @package AppBundle\Command
 */
class InstallCommand extends AbstractCommand
{

    /** @var Filesystem */
    private $filesystem;

    /**
     * InstallCommand constructor.
     *
     * @param null $name
     * @param Filesystem $filesystem
     */
    public function __construct(
        $name,
        Filesystem $filesystem
    ) {
        if (is_null($name)) {
            $name = NULL;
        }
        $this->filesystem = $filesystem;

        parent::__construct($name);
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('app:install')
            ->setDescription('Perform application configuration.');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Checking application requirements.');

        $this->configureFileTemplates();

        // Prepare workspace directories.
        $workspaceDirectories = [
            Workspace::BASE_DIR,
            Workspace::PLATFORM_DIR,
            Workspace::PLATFORM_TAGS,
            Workspace::BUILD_DIR,
        ];

        foreach ($workspaceDirectories as $directory) {
            if (!$this->filesystem->exists($directory)) {
                $this->filesystem->mkdir($directory);
                $this->io->success(
                    sprintf('Directory %s has been created.', $directory)
                );
            } else {
                $this->io->success(sprintf('Directory %s exist.', $directory));
            }
        }

        $this->filesystem->chmod('var/', 0777, 0000, true);

        try {
            $command = $this->getApplication()->find('app:check-requirements');
            $command->run($input, $output);
        } catch (\Exception $exception) {
            $this->stopExecution($exception->getMessage());
        }

        $this->io->title(sprintf('Add following lines to your host file:'));
        $this->io->writeln('### Local environment');
        $this->io->writeln(
            sprintf('%s local-env.jjconsumer.com', VirtualMachine::MACHINE_IP_ADDRESS)
        );
        $this->io->writeln('');
        $this->io->title('Application URL:');
        $this->io->writeln('http://local-env.jjconsumer.com');
        $this->io->writeln('');
    }

    private function configureFileTemplates()
    {
        $fileSyncExcludeFileTemplates = [
            'file-sync-exclude.txt.dist',
            'post-install.drush-commands.yml.dist',
        ];

        foreach ($fileSyncExcludeFileTemplates as $fileSyncExcludeFileTemplate) {
            $fileSyncExcludeFile = str_replace(
                '.dist',
                '',
                $fileSyncExcludeFileTemplate
            );

            if (false == $this->filesystem->exists($fileSyncExcludeFile)) {
                $this->filesystem->copy(
                    $fileSyncExcludeFileTemplate,
                    $fileSyncExcludeFile
                );
                $this->io->success('File '.$fileSyncExcludeFile.' has been created');
            } else {
                $this->io->success('File '.$fileSyncExcludeFile.' exist');
            }
        }
    }
}
