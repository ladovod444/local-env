<?php

namespace App\Event;

//use Symfony\Component\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class EnvironmentCleanup
 *
 * @package App\Event
 */
final class EnvironmentCleanup extends Event
{

    const PHASE_PRE_CLEANUP = 'environment.cleanup.prepare';

    const PHASE_DELETE_DATABASE = 'environment.cleanup.database';

    const PHASE_DELETE_FILES = 'environment.cleanup.files';

    /**
     * @var string
     */
    private $pathToSite;


    /**
     * @var string
     */
    private $canonicalDatabaseName;

    /**
     * EnvironmentCleanup constructor.
     *
     * @param string $pathToSite
     * @param string $canonicalDatabaseName
     */
    public function __construct($pathToSite, $canonicalDatabaseName)
    {
        $this->pathToSite = $pathToSite;
        $this->canonicalDatabaseName = $canonicalDatabaseName;
    }

    /**
     * @return string[]
     */
    public static function getPhases()
    {
        return [
            self::PHASE_PRE_CLEANUP,
            self::PHASE_DELETE_DATABASE,
            self::PHASE_DELETE_FILES,
        ];
    }

    /**
     * @return string
     */
    public function getPathToSite()
    {
        return $this->pathToSite;
    }

    /**
     * @return string
     */
    public function getCanonicalDatabaseName()
    {
        return $this->canonicalDatabaseName;
    }
}
