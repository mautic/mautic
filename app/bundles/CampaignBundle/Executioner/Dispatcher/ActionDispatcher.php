<?php

namespace Mautic\CampaignBundle\Executioner\Dispatcher;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ExecutedBatchEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ActionDispatcher
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
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var LegacyEventDispatcher
     */
    private $legacyDispatcher;

    /**
     * EventDispatcher constructor.
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        EventScheduler $scheduler,
        NotificationHelper $notificationHelper,
        LegacyEventDispatcher $legacyDispatcher
    ) {
        $this->dispatcher         = $dispatcher;
        $this->logger             = $logger;
        $this->scheduler          = $scheduler;
        $this->notificationHelper = $notificationHelper;
        $this->legacyDispatcher   = $legacyDispatcher;
    }

    /**
     * @return PendingEvent
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     */
    public function dispatchEvent(ActionAccessor $config, Event $event, ArrayCollection $logs, PendingEvent $pendingEvent = null)
    {
        if (!$pendingEvent) {
            $pendingEvent = new PendingEvent($config, $event, $logs);
        }

        // this if statement can be removed when legacy dispatcher is removed
        if ($customEvent = $config->getBatchEventName()) {
            $this->dispatcher->dispatch($customEvent, $pendingEvent);

            $success = $pendingEvent->getSuccessful();
            $failed  = $pendingEvent->getFailures();

            $this->validateProcessedLogs($logs, $success, $failed);

            if ($success) {
                $this->dispatchExecutedEvent($config, $event, $success);
            }

            if ($failed) {
                $this->dispatchedFailedEvent($config, $failed);
            }

            // Dispatch legacy ON_EVENT_EXECUTION event for BC
            $this->legacyDispatcher->dispatchExecutionEvents($config, $success, $failed);
        }

        // Execute BC eventName or callback. Or support case where the listener has been converted to batchEventName but still wants to execute
        // eventName for BC support for plugins that could be listening to it's own custom event.
        $this->legacyDispatcher->dispatchCustomEvent($config, $logs, ($customEvent), $pendingEvent);

        return $pendingEvent;
    }

    private function dispatchExecutedEvent(AbstractEventAccessor $config, Event $event, ArrayCollection $logs)
    {
        if (!$logs->count()) {
            return;
        }

        foreach ($logs as $log) {
            $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_EXECUTED,
                new ExecutedEvent($config, $log)
            );
        }

        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_EXECUTED_BATCH,
            new ExecutedBatchEvent($config, $event, $logs)
        );
    }

    private function dispatchedFailedEvent(AbstractEventAccessor $config, ArrayCollection $logs)
    {
        if (!$logs->count()) {
            return;
        }

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $this->logger->debug(
                'CAMPAIGN: '.ucfirst($log->getEvent()->getEventType()).' ID# '.$log->getEvent()->getId().' for contact ID# '.$log->getLead()->getId()
            );

            $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_FAILED,
                new FailedEvent($config, $log)
            );

            $this->notificationHelper->notifyOfFailure($log->getLead(), $log->getEvent());
        }

        $this->scheduler->rescheduleFailures($logs);
    }

    /**
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
