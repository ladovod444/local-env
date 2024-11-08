<?php

namespace App\Services;

use App\Event\EnvironmentCleanup;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EnvironmentCleaner
 *
 * @package AppBundle\Services
 */
class EnvironmentCleaner
{

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @var LocalSiteManager
     */
    private $localSiteManager;

    /**
     * EnvironmentCleaner constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param LocalSiteManager         $localSiteManager
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, LocalSiteManager $localSiteManager)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->localSiteManager = $localSiteManager;
    }

    /**
     * @param string $siteIdentifier
     *
     * @throws \Exception
     */
    public function cleanup($siteIdentifier)
    {

        try {
            $canonicalDatabaseName = $this->localSiteManager->getSiteDatabase($siteIdentifier);
        } catch (\Exception $exception) {
            $canonicalDatabaseName = null;
        }
        finally {
            // Start cleanup process even if database is not exist.
            $sitePath = $sitePath = $this->localSiteManager->buildSiteAbsolutePath($siteIdentifier);
            $event = new EnvironmentCleanup($sitePath, $canonicalDatabaseName);

            foreach (EnvironmentCleanup::getPhases() as $phase) {
                $this->eventDispatcher->dispatch($phase, $event);
            }
        }
    }
}
