<?php

namespace App\EventListener\SiteCleanup;

use App\Event\EnvironmentCleanup;
use App\Process\ProcessAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractSiteCleanupListener
 *
 * @package AppBundle\EventListener\SiteCleanup
 */
abstract class AbstractSiteCleanupListener
{

    use ProcessAwareTrait;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * AbstractSiteInstallListener constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param EnvironmentCleanup $event
     * @param null               $eventName
     */
    public function run(EnvironmentCleanup $event, $eventName = null){
        $this->logger->warning(sprintf('<info>%s</info> has been started.', $eventName));

        try{
            $this->processEvent($event);
        }catch (\Exception $e){
            $this->logger->error(sprintf('Phase <error>%s</error> has been skipped.', $eventName));
            $this->logger->error(sprintf('Reason: <error>%s</error>', $e->getMessage()));
        }

        $this->logger->warning(sprintf('<info>%s</info> has been completed.', $eventName));
        $this->logger->info('');
    }

    /**
     * @param EnvironmentCleanup $event
     *
     * @return mixed
     */
    abstract protected function processEvent(EnvironmentCleanup $event);
}
