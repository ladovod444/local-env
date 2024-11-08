<?php

namespace App\Command;

use App\Services\GitDriver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class CheckRequirementsCommand
 *
 * @package AppBundle\Command
 */
class CheckRequirementsCommand extends AbstractCommand
{
    /** @var string */
    private $pathToAcquiaSshKey;
    /** @var string */
    private $platformRepositoryUrl;
    /** @var GitDriver */
    private $gitDriver;

    /** {@inheritdoc} */
    public function __construct($name, $pathToAcquiaSshKey, $platformRepositoryUrl, GitDriver $gitDriver)
    {
        if (is_null($name)) {
            $name = NULL;
        }
        $this->pathToAcquiaSshKey = $pathToAcquiaSshKey[0];
        $this->platformRepositoryUrl = $platformRepositoryUrl;
        $this->gitDriver = $gitDriver;

        parent::__construct($name);
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('app:check-requirements')
            ->setHidden(true);
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkAcquiaSshKeyExistence($this->pathToAcquiaSshKey);
        $this->testConnectionToRepository($this->platformRepositoryUrl);
    }

    /**
     * @param string $pathToKey
     */
    private function checkAcquiaSshKeyExistence($pathToKey)
    {
        if (empty($pathToKey) || false == file_exists($pathToKey)) {
            $message = sprintf('Acquia SSH key %s not found.', $pathToKey);
            $this->stopExecution($message);
        }

        $this->io->success(sprintf('Acquia SSH key %s was found', $pathToKey));
    }

    /**
     * @param string $repositoryUrl
     */
    private function testConnectionToRepository($repositoryUrl)
    {
        try{
            $this->gitDriver->testConnectionToRepository($repositoryUrl);
        }catch (\Exception $exception){
            $message = sprintf('Connection to remote repository "%s" was failed. Reason: %s', $repositoryUrl, $exception->getMessage());
            $this->stopExecution($message);
        }

        $this->io->success(sprintf('Remote repository %s is available', $repositoryUrl));
    }
}
