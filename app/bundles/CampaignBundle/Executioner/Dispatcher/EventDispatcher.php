<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Dispatcher;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EventDispatcher.
 */
class EventDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var LegacyEventDispatcher
     */
    private $legacyDispatcher;

    /**
     * EventDispatcher constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     * @param LegacyEventDispatcher    $legacyDispatcher
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        EventScheduler $scheduler,
        LegacyEventDispatcher $legacyDispatcher
    ) {
        $this->dispatcher       = $dispatcher;
        $this->logger           = $logger;
        $this->scheduler        = $scheduler;
        $this->legacyDispatcher = $legacyDispatcher;
    }

    /**
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $logs
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     */
    public function executeEvent(AbstractEventAccessor $config, Event $event, ArrayCollection $logs)
    {
        // this if statement can be removed when legacy dispatcher is removed
        if ($customEvent = $config->getBatchEventName()) {
            $pendingEvent = new PendingEvent($config, $event, $logs);
            $this->dispatcher->dispatch($customEvent, $pendingEvent);

            $success = $pendingEvent->getSuccessful();
            $this->dispatchExecutedEvent($config, $success);

            $failed = $pendingEvent->getFailures();
            $this->dispatchedFailedEvent($config, $failed);

            $this->validateProcessedLogs($logs, $success, $failed);

            // Dispatch legacy ON_EVENT_EXECUTION event for BC
            $this->legacyDispatcher->dispatchExecutionEvents($config, $success, $failed);
        }

        // Execute BC eventName or callback. Or support case where the listener has been converted to batchEventName but still wants to execute
        // eventName for BC support for plugins that could be listening to it's own custom event.
        $this->legacyDispatcher->dispatchCustomEvent($config, $event, $logs, ($customEvent));
    }

    /**
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $logs
     */
    public function dispatchExecutedEvent(AbstractEventAccessor $config, ArrayCollection $logs)
    {
        foreach ($logs as $log) {
            $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_EXECUTED,
                new ExecutedEvent($config, $log)
            );
        }
    }

    /**
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $logs
     */
    public function dispatchedFailedEvent(AbstractEventAccessor $config, ArrayCollection $logs)
    {
        foreach ($logs as $log) {
            $this->logger->debug(
                'CAMPAIGN: '.ucfirst($log->getEvent()->getEventType()).' ID# '.$log->getEvent()->getId().' for contact ID# '.$log->getLead()->getId()
            );

            $this->scheduler->rescheduleFailure($log);

            $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_FAILED,
                new FailedEvent($config, $log)
            );
        }
    }

    /**
     * @param ArrayCollection $pending
     * @param ArrayCollection $success
     * @param ArrayCollection $failed
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     */
    private function validateProcessedLogs(ArrayCollection $pending, ArrayCollection $success, ArrayCollection $failed)
    {
        foreach ($pending as $log) {
            if (!$success->contains($log) && !$failed->contains($log)) {
                throw new LogNotProcessedException($log);
            }

            if ($success->contains($log) && $failed->contains($log)) {
                throw new LogPassedAndFailedException($log);
            }
        }
    }
}
