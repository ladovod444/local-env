<?php

namespace App\EventListener\SiteCleanup;

use App\Event\EnvironmentCleanup;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DeleteFiles
 *
 * @package AppBundle\EventListener\SiteCleanup
 */
class DeleteFiles extends AbstractSiteCleanupListener
{

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * DeleteFiles constructor.
     *
     * @param LoggerInterface $logger
     * @param Filesystem      $filesystem
     */
    public function __construct(LoggerInterface $logger, Filesystem $filesystem)
    {
        parent::__construct($logger);
        $this->filesystem = $filesystem;
    }

    /**
     * @param \App\Event\EnvironmentCleanup $event
     *
     * @return mixed|void
     */
    protected function processEvent(EnvironmentCleanup $event)
    {
        $pathToSite = $event->getPathToSite();
        if(false == $this->filesystem->exists($pathToSite)){
            $this->logger->error(sprintf('Site %s not found', $pathToSite));
            return;
        }

        try{
            $this->filesystem->chmod($pathToSite, 0777, 0000, true);
        }catch (\Exception $e){
            $this->logger->error($e->getMessage());
        }
        $this->filesystem->remove($pathToSite);
    }
}
