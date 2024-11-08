<?php

namespace App\Services;

use App\Model\Workspace;

/**
 * Class PlatformManager
 *
 * @package AppBundle\Services
 */
final class PlatformManager
{

    /** @var string */
    private $platformRepositoryURL;

    /** @var GitDriver */
    private $git;

    /**
     * PlatformManager constructor.
     *
     * @param GitDriver $gitDriver
     * @param string    $platformRepositoryURL
     */
    public function __construct(GitDriver $gitDriver, $platformRepositoryURL)
    {
        $this->git = $gitDriver;
        $this->platformRepositoryURL = $platformRepositoryURL;
    }

    /**
     * Download platform code.
     */
    public function clonePlatform()
    {
        $this->git->getRepositoryOrClone(Workspace::PLATFORM_DIR, $this->platformRepositoryURL);
        try {
            $this->git->update(Workspace::PLATFORM_DIR);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Update platform to latest version.
     */
    public function update()
    {
        try {
            $this->git->update(Workspace::PLATFORM_DIR);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Get platform tags.
     *
     * @return array
     */
    public function getTagsList()
    {
        return $this->git->getTagsList(Workspace::PLATFORM_DIR);
    }
}
