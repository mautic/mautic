<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\CampaignBundle\Executioner\Exception\NoEventsFoundException;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ScheduledExecutioner implements ExecutionerInterface
{
    /**
     * @var LeadEventLogRepository
     */
    private $repo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventExecutioner
     */
    private $executioner;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var ScheduledContactFinder
     */
    private $scheduledContactFinder;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var ContactLimiter
     */
    private $limiter;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var array
     */
    private $scheduledEvents;

    /**
     * @var Counter
     */
    private $counter;

    /**
     * @var \DateTime
     */
    private $now;

    /**
     * ScheduledExecutioner constructor.
     *
     * @param LeadEventLogRepository $repository
     * @param LoggerInterface        $logger
     * @param TranslatorInterface    $translator
     * @param EventExecutioner       $executioner
     * @param EventScheduler         $scheduler
     * @param ScheduledContactFinder $scheduledContactFinder
     */
    public function __construct(
        LeadEventLogRepository $repository,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        EventExecutioner $executioner,
        EventScheduler $scheduler,
        ScheduledContactFinder $scheduledContactFinder
    ) {
        $this->repo                   = $repository;
        $this->logger                 = $logger;
        $this->translator             = $translator;
        $this->executioner            = $executioner;
        $this->scheduler              = $scheduler;
        $this->scheduledContactFinder = $scheduledContactFinder;
    }

    /**
     * @param Campaign             $campaign
     * @param ContactLimiter       $limiter
     * @param OutputInterface|null $output
     *
     * @return Counter|mixed
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function execute(Campaign $campaign, ContactLimiter $limiter, OutputInterface $output = null)
    {
        $this->campaign   = $campaign;
        $this->limiter    = $limiter;
        $this->output     = ($output) ? $output : new NullOutput();
        $this->counter    = new Counter();

        $this->logger->debug('CAMPAIGN: Triggering scheduled events');

        try {
            $this->prepareForExecution();
            $this->executeOrRecheduleEvent();
        } catch (NoEventsFoundException $exception) {
            $this->logger->debug('CAMPAIGN: No events to process');
        } finally {
            if ($this->progressBar) {
                $this->progressBar->finish();
            }
        }

        return $this->counter;
    }

    /**
     * @param array                $logIds
     * @param OutputInterface|null $output
     *
     * @return Counter
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function executeByIds(array $logIds, OutputInterface $output = null)
    {
        $this->output  = ($output) ? $output : new NullOutput();
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
        $now       = new \DateTime();
        foreach ($organized as $organizedLogs) {
            /** @var Event $event */
            $event = $organizedLogs->first()->getEvent();

            // Validate that the schedule is still appropriate
            $this->validateSchedule($organizedLogs, $now, true);

            // Check that the campaign is published with up/down dates
            if ($event->getCampaign()->isPublished()) {
                try {
                    // Hydrate contacts with custom field data
                    $this->scheduledContactFinder->hydrateContacts($organizedLogs);

                    $this->executioner->executeLogs($event, $organizedLogs, $this->counter);
                } catch (NoContactsFoundException $e) {
                    // All of the events were rescheduled
                }
            } else {
                $this->executioner->recordLogsWithError(
                    $organizedLogs,
                    $this->translator->trans('mautic.campaign.event.campaign_unpublished')
                );
            }

            $this->progressBar->advance($organizedLogs->count());
        }

        $this->progressBar->finish();

        return $this->counter;
    }

    /**
     * @throws NoEventsFoundException
     */
    private function prepareForExecution()
    {
        $this->progressBar = null;
        $this->now         = new \Datetime();

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
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function executeOrRecheduleEvent()
    {
        // Use the same timestamp across all contacts processed
        $now = new \DateTime();

        foreach ($this->scheduledEvents as $eventId) {
            $this->counter->advanceEventCount();

            // Loop over contacts until the entire campaign is executed
            $this->executeScheduled($eventId, $now);
        }
    }

    /**
     * @param           $eventId
     * @param \DateTime $now
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function executeScheduled($eventId, \DateTime $now)
    {
        $logs = $this->repo->getScheduled($eventId, $this->now, $this->limiter);
        while ($logs->count()) {
            try {
                $this->scheduledContactFinder->hydrateContacts($logs);
            } catch (NoContactsFoundException $e) {
                break;
            }

            $event = $logs->first()->getEvent();
            $this->progressBar->advance($logs->count());
            $this->counter->advanceEvaluated($logs->count());

            // Validate that the schedule is still appropriate
            $this->validateSchedule($logs, $now);

            // Execute if there are any that did not get rescheduled
            $this->executioner->executeLogs($event, $logs, $this->counter);

            // Get next batch
            $this->scheduledContactFinder->clear();
            $logs = $this->repo->getScheduled($eventId, $this->now, $this->limiter);
        }
    }

    /**
     * @param ArrayCollection $logs
     * @param \DateTime       $now
     * @param bool            $scheduleTogether
     *
     * @throws Scheduler\Exception\NotSchedulableException
     */
    private function validateSchedule(ArrayCollection $logs, \DateTime $now, $scheduleTogether = false)
    {
        $toBeRescheduled     = new ArrayCollection();
        $latestExecutionDate = $now;

        // Check if the event should be scheduled (let the schedulers do the debug logging)
        /** @var LeadEventLog $log */
        foreach ($logs as $key => $log) {
            $executionDate = $this->scheduler->validateExecutionDateTime($log, $now);
            $this->logger->debug(
                'CAMPAIGN: Log ID #'.$log->getID().
                ' to be executed on '.$executionDate->format('Y-m-d H:i:s').
                ' compared to '.$now->format('Y-m-d H:i:s')
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
     * @param ArrayCollection $logs
     *
     * @return ArrayCollection[]
     */
    private function organizeByEvent(ArrayCollection $logs)
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

        return array_merge($other, $jumpTo);
    }
}
