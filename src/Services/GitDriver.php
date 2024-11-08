<?php

namespace App\Services;

use App\Model\ConsoleCommand;
use GitWrapper\GitWrapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class GitDriver
 *
 * @package AppBundle\Services
 */
final class GitDriver
{

    /** @var GitWrapper */
    private $wrapper;

    /** @var Filesystem */
    private $fileSystem;

    /** @var LoggerInterface */
    private $logger;

    /**
     * GitDriver constructor.
     *
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->fileSystem = $filesystem;
        $this->logger = $logger;

        $this->initGitWrapper();
    }

    /**
     * @param string $targetDirectory
     * @param string $repositoryUrl
     */
    public function getRepositoryOrClone($targetDirectory, $repositoryUrl)
    {
        if ($this->fileSystem->exists($targetDirectory)) {
            $this->logger->notice(sprintf('Repository directory already exist. Cleaning up directory %s', $targetDirectory));
            $this->fileSystem->remove($targetDirectory);
        }

        $this->logger->notice('Creating repository directory ' . $targetDirectory);
        $this->fileSystem->mkdir($targetDirectory);

        if (false == $this->wrapper->workingCopy($targetDirectory)->isCloned()) {
            //dump($this->wrapper->streamOutput()); die();
            $this->wrapper->streamOutput();
          dump(sprintf('clone %s %s --verbose --progress', $repositoryUrl, $targetDirectory));
            $this->wrapper->git(sprintf('clone %s %s --verbose --progress', $repositoryUrl, $targetDirectory));
        }
    }

    /**
     * Update target repository.
     *
     * @param string $targetDirectory
     * @throws \Exception
     */
    public function update($targetDirectory)
    {
        $workingCopy = $this->wrapper->workingCopy($targetDirectory);

        if(false == $workingCopy->isCloned()){
            $message = sprintf('Nothing to update. Looks like target directory %s is not git repository', $targetDirectory);
            throw new \Exception($message);
        }

        $this->wrapper->streamOutput();
        $this->fetchAll($targetDirectory);

        $workingCopy->pull('origin');
    }

    /**
     * @param string $targetDirectory
     *
     * @return string
     */
    public function fetchAll($targetDirectory)
    {
        $workingCopy = $this->wrapper->workingCopy($targetDirectory);

        return $workingCopy->fetchAll(['v' => true])->getOutput();
    }

    /**
     * @param string $targetDirectory
     *
     * @return array
     */
    public function getTagsList($targetDirectory)
    {
        $git = $this->wrapper->workingCopy($targetDirectory);

        $tags = explode(PHP_EOL, $git->tag('--list')->getOutput());

        natsort($tags);
        $tags = array_filter(
            $tags,
            function ($item) {
                return !empty($item);
            }
        );

        return $tags;
    }

    /**
     * @param string $targetDirectory
     *
     * @return string
     */
    public function getCurrentTag($targetDirectory)
    {
        $git = $this->wrapper->workingCopy($targetDirectory);
        $tag = $this->wrapper->git('describe --tags');

        return $tag;
    }

    /**
     * @param $pathToRepo
     * @param $tag
     */
    public function checkoutTag($pathToRepo, $tag)
    {
        $this->wrapper->workingCopy($pathToRepo)->checkout(
          sprintf('tags/%s', $tag),
          '--force'
        );
    }

    /**
     * Switch branch in selected repository.
     *
     * git checkout -b release/OS-7.x-2.14-dev origin/release/OS-7.x-2.14-dev --
     *
     * @param string $siteDirectory
     * @param string $branchName
     */
    public function switchBranch($siteDirectory, $branchName)
    {
        $this->wrapper->streamOutput();

        $workingCopy = $this->wrapper->workingCopy($siteDirectory);
        $allBranches = $workingCopy->getBranches()->all();

        // If branch exist skip switching.
        if (in_array($branchName, $allBranches)) {
            $message = sprintf('Branch "%s" already exist', $branchName);
            $this->logger->info($message);

            return;
        }

        $options = [
          'progress' => true,
          't' => sprintf('origin/%s', $branchName),
        ];
        $workingCopy->checkoutNewBranch($branchName, $options);
    }

    /**
     * @param string $repositoryUrl
     * @return string
     */
    public function testConnectionToRepository($repositoryUrl)
    {
        return $this->wrapper->git(sprintf('ls-remote -h %s', $repositoryUrl));
    }

    /**
     * Init GitWrapper.
     */
    private function initGitWrapper()
    {
        $this->wrapper = new GitWrapper('/usr/bin/git');
        // $this->wrapper->setEnvVar('GIT_SSH_VARIANT', 'ssh');
        //$this->wrapper->setProcOptions(['--verbose', '--progress']);
        $this->wrapper->setTimeout(ConsoleCommand::PROCESS_TIMEOUT);
    }

    /**
     * Returns the GitWrapper object that likely instantiated this class.
     *
     * @return \GitWrapper\GitWrapper
     */
    public function getWrapper()
    {
        return $this->wrapper;
    }

    /**
     * Get the platform version.
     *
     * @param string $path
     *   Path to a platform directory.
     *
     * @return array
     */
    public function getPlatformVersion($path) {
        $response = [];
        $workingCopy = $this->wrapper->workingCopy($path);

        // The .git directory is not exist. Abort!
        if (!$workingCopy->isCloned()) {
          return $response;
        }

        // Try to get exact match a git tag if possible (this will work only if
        // our platform is on some specific tag, otherwise - an exception will
        // be gotten).
        try {
            $cmd = 'describe --tags --exact-match';
            $workingDir = $workingCopy->getDirectory();

            $response = [
                'type' => 'tag',
                'version' => $workingCopy->getWrapper()->git($cmd, $workingDir),
            ];
        }
        catch (\Exception $e) {
            // Get a platform branch or specific commit hash (in case, we use
            // specific commit).
            $output = $workingCopy->branch()->getOutput();
            preg_match('/\* (?<branch>.+[\S]?)/', $output, $matches);

            // Specific hash can be used!
            preg_match('/\(HEAD detached at (?<hash>.+?)\)/', $matches['branch'], $matches2);

            $response = [
                'type' => isset($matches2['hash']) ? 'hash' : 'branch',
                'version' => isset($matches2['hash']) ? $matches2['hash'] : $matches['branch'],
            ];
        }

        return $response;
    }
}
