<?php

namespace App\Exception;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Define a manager exception class.
 */
class ManagerException extends \Exception
{

    /**
     * {@inheritdoc}
     */
    public function __construct($message, $code = 0, \Exception $previous = null, $targetDir = null)
    {
        if ($targetDir) {
            $this->cleanup($targetDir);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Cleanup a site directory before show an exception.
     *
     * @param string $targetDir
     *   A directory to cleanup.
     */
    public function cleanup($targetDir)
    {
        try {
            $fileSystem = new Filesystem();
            $fileSystem->remove($targetDir);
        } catch (\Exception $e) {
            // Permission issue? Let's leave it empty for now!
        }
    }
}
