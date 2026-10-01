<?php

namespace Mautic\CampaignBundle\Executioner\Dispatcher;

use Doctrine\Common\Collections\Collection;
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
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class ActionDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
        private EventScheduler $scheduler,
    ) {
    }

    /**
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     */
    public function dispatchEvent(ActionAccessor $config, Event $event, Collection $logs, ?PendingEvent $pendingEvent = null): PendingEvent
    {
        if (!$pendingEvent) {
            $pendingEvent = new PendingEvent($config, $event, $logs);
        }

        if ($batchEventName = $config->getBatchEventName()) {
            $this->dispatcher->dispatch($pendingEvent, $batchEventName);

            $success = $pendingEvent->getSuccessful();
            $failed  = $pendingEvent->getFailures();

            $this->validateProcessedLogs($logs, $success, $failed);

            if ($success->count()) {
                $this->dispatchExecutedEvent($config, $event, $success);
            }

            if ($failed->count()) {
                $this->dispatchFailedEvent($config, $failed);
            }
        }

        return $pendingEvent;
    }

    /**
     * @param Collection<int, LeadEventLog> $logs
     */
    private function dispatchExecutedEvent(AbstractEventAccessor $config, Event $event, Collection $logs): void
    {
        if (!$logs->count()) {
            return;
        }

        foreach ($logs as $log) {
            $this->dispatcher->dispatch(
                new ExecutedEvent($config, $log)
            );
        }

        $this->dispatcher->dispatch(
            new ExecutedBatchEvent($config, $event, $logs)
        );
    }

    /**
     * @param Collection<int, LeadEventLog> $logs
     */
    private function dispatchFailedEvent(AbstractEventAccessor $config, Collection $logs): void
    {
        if (!$logs->count()) {
            return;
        }

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $this->logger->debug(
                'CAMPAIGN: '.ucfirst($log->getEvent()->getEventType() ?? 'unknown event').' ID# '.$log->getEvent()->getId().' for contact ID# '.$log->getLead()->getId()
            );

            $this->dispatcher->dispatch(
                new FailedEvent($config, $log)
            );
        }

        $this->scheduler->rescheduleFailures($logs);
    }

    /**
     * @param Collection<int, LeadEventLog> $pending
     * @param Collection<int, LeadEventLog> $success
     * @param Collection<int, LeadEventLog> $failed
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     */
    private function validateProcessedLogs(Collection $pending, Collection $success, Collection $failed): void
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
