<?php

namespace App\EventListener\NewCloneInstall;

use App\Event\NewCloneInstall;
use App\Process\ProcessAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractNewCloneInstallListener
 *
 * @package AppBundle\EventListener
 */
abstract class AbstractNewCloneInstallListener
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
     * @param NewCloneInstall $newCloneInstallEvent
     *
     * @param null $eventName
     */
    public function run(NewCloneInstall $newCloneInstallEvent, $eventName = null)
    {
        $this->logger->warning(
            sprintf('Phase <info>%s</info> step has been started', $eventName)
        );

        $this->processEvent($newCloneInstallEvent);

        $this->logger->warning(
            sprintf('Phase <info>%s</info> has been completed', $eventName)
        );

    }

    /**
     * @param NewCloneInstall $newCloneInstallEvent
     *
     * @return mixed
     */
    abstract protected function processEvent(NewCloneInstall $newCloneInstallEvent);
}
