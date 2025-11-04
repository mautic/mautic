<?php

namespace Mautic\CampaignBundle\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\CampaignBundle\Executioner\Exception\NoEventsFoundException;
use Mautic\CampaignBundle\Executioner\Helper\EventRedirectionHelper;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\ProcessSignal\Exception\SignalCaughtException;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ScheduledExecutioner implements ExecutionerInterface, ResetInterface
{
    private ?Campaign $campaign = null;

    private ?ContactLimiter $limiter = null;

    private ?OutputInterface $output = null;

    private ?ProgressBar $progressBar = null;

    private ?array $scheduledEvents = null;

    private ?Counter $counter = null;

    protected ?\DateTime $now = null;

    public function __construct(
        private LeadEventLogRepository $repo,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private EventExecutioner $executioner,
        private EventScheduler $scheduler,
        private ScheduledContactFinder $scheduledContactFinder,
        private ProcessSignalService $processSignalService,
        private EntityManagerInterface $entityManager,
        private EventRedirectionHelper $eventRedirectionHelper,
        private LeadRepository $leadRepository,
    ) {
    }

    /**
     * @return Counter|mixed
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     * @throws CannotProcessEventException
     * @throws NotSchedulableException
     * @throws QueryException
     */
    public function execute(Campaign $campaign, ContactLimiter $limiter, ?OutputInterface $output = null)
    {
        $this->campaign   = $campaign;
        $this->limiter    = $limiter;
        $this->output     = $output ?: new NullOutput();
        $this->counter    = new Counter();

        $this->logger->debug('CAMPAIGN: Triggering scheduled events');

        try {
            $this->prepareForExecution();
            $this->executeOrRescheduleEvent();
        } catch (NoEventsFoundException) {
            $this->logger->debug('CAMPAIGN: No events to process');
        } finally {
            if ($this->progressBar) {
                $this->progressBar->finish();
            }
        }

        return $this->counter;
    }

    /**
     * @return Counter
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     * @throws CannotProcessEventException
     * @throws NotSchedulableException
     * @throws QueryException
     */
    public function executeByIds(array $logIds, ?OutputInterface $output = null, ?\DateTime $now = null)
    {
        $now           = $now ?? $this->now ?? new \DateTime();
        $this->output  = $output ?: new NullOutput();
        $this->counter = new Counter();

        if (!$logIds) {
            return $this->counter;
        }

        $logs           = $this->repo->getScheduledByIds($logIds);
        $totalLogsFound = $logs->count();
        $this->counter->advanceEvaluated($totalLogsFound);

        $this->logger->debug('CAMPAIGN: '.$logs->count().' events scheduled to execute.');
        $this->output->writeln(
            $this->translator->trans(
                'mautic.campaign.trigger.event_count',
                [
                    '%events%' => $totalLogsFound,
                    '%batch%'  => 'n/a',
                ]
            )
        );

        if (!$logs->count()) {
            return $this->counter;
        }

        $this->progressBar = ProgressBarHelper::init($this->output, $totalLogsFound);
        $this->progressBar->start();

        $scheduledLogCount = $totalLogsFound - $logs->count();
        $this->progressBar->advance($scheduledLogCount);

        // Organize the logs by event ID
        $organized = $this->organizeByEvent($logs);
        foreach ($organized as $organizedLogs) {
            /** @var Event $event */
            $event = $organizedLogs->first()->getEvent();

            $event = $this->handlePossibleEventRedirection($event, $organizedLogs);

            // Validate that the schedule is still appropriate
            $this->validateSchedule($organizedLogs, $now, true);

            // Check that the campaign is published with up/down dates
            if ($event->getCampaign()->isPublished() && !$organizedLogs->isEmpty()) {
                try {
                    // Hydrate contacts with custom field data
                    $this->scheduledContactFinder->hydrateContacts($organizedLogs);

                    $this->executioner->executeLogs($event, $organizedLogs, $this->counter);
                } catch (NoContactsFoundException) {
                    // All of the events were rescheduled
                }
            }

            $this->progressBar->advance($organizedLogs->count());
        }

        $this->progressBar->finish();

        return $this->counter;
    }

    public function reset(): void
    {
        $this->now = null;
    }

    /**
     * @throws NoEventsFoundException
     */
    private function prepareForExecution(): void
    {
        $this->now ??= new \DateTime();

        // Get counts by event
        $scheduledEvents       = $this->repo->getScheduledCounts($this->campaign->getId(), $this->now, $this->limiter);
        $totalScheduledCount   = $scheduledEvents ? array_sum($scheduledEvents) : 0;
        $this->scheduledEvents = array_keys($scheduledEvents);
        $this->logger->debug('CAMPAIGN: '.$totalScheduledCount.' events scheduled to execute.');

        $this->output->writeln(
            $this->translator->trans(
                'mautic.campaign.trigger.event_count',
                [
                    '%events%' => $totalScheduledCount,
                    '%batch%'  => $this->limiter->getBatchLimit(),
                ]
            )
        );

        if (!$totalScheduledCount) {
            throw new NoEventsFoundException();
        }

        $this->progressBar = ProgressBarHelper::init($this->output, $totalScheduledCount);
        $this->progressBar->start();
    }

    /**
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     * @throws CannotProcessEventException
     * @throws NotSchedulableException
     * @throws QueryException
     */
    private function executeOrRescheduleEvent(): void
    {
        // Use the same timestamp across all contacts processed
        $now = $this->now ?? new \DateTime();

        foreach ($this->scheduledEvents as $eventId) {
            $this->counter->advanceEventCount();

            // Loop over contacts until the entire campaign is executed
            $this->executeScheduled($eventId, $now);
        }
    }

    /**
     * @param int       $eventId The ID of the event to execute
     * @param \DateTime $now     The current timestamp
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     * @throws CannotProcessEventException
     * @throws NotSchedulableException
     * @throws QueryException|SignalCaughtException
     */
    private function executeScheduled(int $eventId, \DateTime $now): void
    {
        $logs = $this->repo->getScheduled($eventId, $this->now, $this->limiter);
        while ($logs->count()) {
            try {
                $fetchedContacts = $this->scheduledContactFinder->hydrateContacts($logs);
            } catch (NoContactsFoundException) {
                break;
            }

            $event = $logs->first()->getEvent();
            $this->progressBar->advance($logs->count());
            $this->counter->advanceEvaluated($logs->count());

            $event = $this->handlePossibleEventRedirection($event, $logs);

            // Validate that the schedule is still appropriate
            $this->validateSchedule($logs, $now);

            // Execute if there are any that did not get rescheduled
            if (!$logs->isEmpty() && $event->getCampaign()->isPublished()) {
                $this->executioner->executeLogs($event, $logs, $this->counter);
            }

            $this->processSignalService->throwExceptionIfSignalIsCaught();

            // Get next batch
            $this->scheduledContactFinder->clear($fetchedContacts);
            $logs = $this->repo->getScheduled($eventId, $this->now, $this->limiter);
        }
    }

    /**
     * Validates and potentially reschedules events based on execution timing.
     *
     * @param ArrayCollection $logs             Collection of event logs
     * @param \DateTime       $now              Current timestamp for comparison
     * @param bool            $scheduleTogether Whether to reschedule all logs together
     *
     * @throws NotSchedulableException
     */
    private function validateSchedule(ArrayCollection $logs, \DateTime $now, bool $scheduleTogether = false): void
    {
        $toBeRescheduled     = new ArrayCollection();
        $latestExecutionDate = $now;

        // Check if the event should be scheduled (let the schedulers do the debug logging)
        /** @var LeadEventLog $log */
        foreach ($logs as $key => $log) {
            $executionDate = $this->scheduler->validateExecutionDateTime($log, $now);
            $this->logger->debug(
                'CAMPAIGN: Log ID #'.$log->getID().
                ' to be executed on '.$executionDate->format('Y-m-d H:i:s e').
                ' compared to '.$now->format('Y-m-d H:i:s e')
            );

            if ($this->scheduler->shouldSchedule($executionDate, $now)) {
                // The schedule has changed for this event since first scheduled
                $this->counter->advanceTotalScheduled();
                if ($scheduleTogether) {
                    $toBeRescheduled->set($key, $log);

                    if ($executionDate > $latestExecutionDate) {
                        $latestExecutionDate = $executionDate;
                    }
                } else {
                    $this->scheduler->reschedule($log, $executionDate);
                }

                $logs->remove($key);

                continue;
            }
        }

        if ($toBeRescheduled->count()) {
            $this->scheduler->rescheduleLogs($toBeRescheduled, $latestExecutionDate);
        }
    }

    /**
     * Handles possible redirection of a deleted event to its redirectEvent.
     * Returns the original event if no redirection occurred, otherwise returns the redirected event.
     *
     * @param Event           $event The event to check for redirection
     * @param ArrayCollection $logs  Collection of event logs
     *
     * @return Event The original event or redirected event
     */
    private function handlePossibleEventRedirection(Event $event, ArrayCollection $logs): Event
    {
        $redirectedEvent = $this->eventRedirectionHelper->handleEventRedirection($event, null, null);

        if ($redirectedEvent === $event) {
            return $event;
        }

        $this->updateLogsForRedirectedEvent($redirectedEvent, $logs, $event);

        return $redirectedEvent;
    }

    /**
     * @param Event                         $redirectEvent The redirected event to update logs for
     * @param Collection<int, LeadEventLog> $logs          Collection of event logs to update
     * @param Event                         $originalEvent The original event before redirection
     *
     * @throws NoResultException|NonUniqueResultException
     */
    private function updateLogsForRedirectedEvent(Event $redirectEvent, Collection $logs,
        Event $originalEvent): void
    {
        if ($logs->isEmpty()) {
            return;
        }

        $contactIds = [];

        foreach ($logs as $log) {
            $contactId    = $log->getLead()->getId();
            $contactIds[] = $contactId;
            $metadata     = $log->getMetadata() ?? [];

            // Store original event information for tracking
            if (!isset($metadata['redirection_history'])) {
                $metadata['redirection_history'] = [];
            }

            // Add to redirection history
            $metadata['redirection_history'][] = [
                'original_event_id' => $originalEvent->getId(),
                'original_rotation' => $log->getRotation(),
                'redirect_time'     => (new \DateTime())->format(DateTimeHelper::FORMAT_DB),
            ];

            $metadata['redirect_applied']     = true;
            $metadata['last_redirected_from'] = $originalEvent->getId();
            $metadata['originalEventName']    = $originalEvent->getName(); // Store the name for display in timeline

            // 1. Find the rotation for this contact/campaign combination.
            $rotation = $this->leadRepository->getContactRotations(
                [$contactId], $redirectEvent->getCampaign()->getId());
            $rotationValue = $rotation[$contactId]['rotation'] ?? 0;
            $newRotation   = $rotationValue + 1;

            // 2. Update the log entity with new event, campaign, rotation, and metadata
            $log->setEvent($redirectEvent);
            $log->setCampaign($redirectEvent->getCampaign());
            $log->setRotation($newRotation);
            $log->setMetadata($metadata);

            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();

        $this->leadRepository->incrementCampaignRotationForContacts(
            array_unique($contactIds),
            $redirectEvent->getCampaign()->getId()
        );

        $this->logger->debug(
            sprintf(
                'CAMPAIGN: Updated %d logs to reference redirected event ID %d',
                count($logs),
                $redirectEvent->getId()
            )
        );
    }

    /**
     * Organizes logs by event ID, separating jump events from other events.
     * Jump to events need to be processed after all other events.
     *
     * @param Collection<int, LeadEventLog> $logs Collection of logs to organize
     *
     * @return Collection<int, ArrayCollection> Organized logs with event IDs as keys
     */
    private function organizeByEvent(Collection $logs): Collection
    {
        $jumpTo = [];
        $other  = [];

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $event     = $log->getEvent();
            $eventType = $event->getType();

            if (CampaignActionJumpToEventSubscriber::EVENT_NAME === $eventType) {
                if (!isset($jumpTo[$event->getId()])) {
                    $jumpTo[$event->getId()] = new ArrayCollection();
                }

                $jumpTo[$event->getId()]->set($log->getId(), $log);
            } else {
                if (!isset($other[$event->getId()])) {
                    $other[$event->getId()] = new ArrayCollection();
                }

                $other[$event->getId()]->set($log->getId(), $log);
            }
        }

        return new ArrayCollection(array_merge($other, $jumpTo));
    }
}
