<?php

namespace App\EventListener\SiteInstall;

use App\Event\SiteInstall;
use App\Process\ProcessAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractSiteInstallListener
 *
 * @package AppBundle\EventListener
 */
abstract class AbstractSiteInstallListener
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
     * @param SiteInstall $siteInstallEvent
     *
     * @param null $eventName
     */
    public function run(SiteInstall $siteInstallEvent, $eventName = null)
    {
        $this->logger->warning(
            sprintf('Phase <info>%s</info> step has been started', $eventName)
        );

        $this->processEvent($siteInstallEvent);

        $this->logger->warning(
            sprintf('Phase <info>%s</info> has been completed', $eventName)
        );

    }

    /**
     * @param SiteInstall $siteInstallEvent
     *
     * @return mixed
     */
    abstract protected function processEvent(SiteInstall $siteInstallEvent);
}
