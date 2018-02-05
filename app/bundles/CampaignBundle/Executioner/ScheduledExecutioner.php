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
use Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContacts;
use Mautic\CampaignBundle\Executioner\Exception\NoEventsFound;
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
     * @var ScheduledContacts
     */
    private $scheduledContacts;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var
     */
    private $contactId;

    /**
     * @var int
     */
    private $batchLimit;

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
     * ScheduledExecutioner constructor.
     *
     * @param LeadEventLogRepository $repository
     * @param LoggerInterface        $logger
     * @param TranslatorInterface    $translator
     * @param EventExecutioner       $executioner
     * @param EventScheduler         $scheduler
     * @param ScheduledContacts      $scheduledContacts
     */
    public function __construct(
        LeadEventLogRepository $repository,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        EventExecutioner $executioner,
        EventScheduler $scheduler,
        ScheduledContacts $scheduledContacts
    ) {
        $this->repo              = $repository;
        $this->logger            = $logger;
        $this->translator        = $translator;
        $this->executioner       = $executioner;
        $this->scheduler         = $scheduler;
        $this->scheduledContacts = $scheduledContacts;
    }

    /**
     * @param Campaign             $campaign
     * @param int                  $batchLimit
     * @param OutputInterface|null $output
     *
     * @return Counter|mixed
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Scheduler\Exception\NotSchedulableException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function executeForCampaign(Campaign $campaign, $batchLimit = 100, OutputInterface $output = null)
    {
        $this->campaign   = $campaign;
        $this->batchLimit = $batchLimit;
        $this->output     = ($output) ? $output : new NullOutput();

        $this->logger->debug('CAMPAIGN: Triggering scheduled events');

        return $this->execute();
    }

    /**
     * @param Campaign             $campaign
     * @param                      $contactId
     * @param OutputInterface|null $output
     *
     * @return Counter|mixed
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Scheduler\Exception\NotSchedulableException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function executeForContact(Campaign $campaign, $contactId, OutputInterface $output = null)
    {
        $this->campaign   = $campaign;
        $this->contactId  = $contactId;
        $this->output     = ($output) ? $output : new NullOutput();
        $this->batchLimit = null;

        return $this->execute();
    }

    /**
     * @return Counter
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Scheduler\Exception\NotSchedulableException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function execute()
    {
        $this->counter = new Counter();

        try {
            $this->prepareForExecution();
            $this->executeOrRecheduleEvent();
        } catch (NoEventsFound $exception) {
            $this->logger->debug('CAMPAIGN: No events to process');
        } finally {
            if ($this->progressBar) {
                $this->progressBar->finish();
                $this->output->writeln("\n");
            }
        }

        return $this->counter;
    }

    /**
     * @throws NoEventsFound
     */
    private function prepareForExecution()
    {
        // Get counts by event
        $scheduledEvents       = $this->repo->getScheduledCounts($this->campaign->getId());
        $totalScheduledCount   = array_sum($scheduledEvents);
        $this->scheduledEvents = array_keys($scheduledEvents);
        $this->logger->debug('CAMPAIGN: '.$totalScheduledCount.' events scheduled to execute.');

        $this->output->writeln(
            $this->translator->trans(
                'mautic.campaign.trigger.event_count',
                [
                    '%events%' => $totalScheduledCount,
                    '%batch%'  => $this->batchLimit,
                ]
            )
        );

        $this->progressBar = ProgressBarHelper::init($this->output, $totalScheduledCount);
        $this->progressBar->start();

        if (!$totalScheduledCount) {
            throw new NoEventsFound();
        }
    }

    /**
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
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
        $logs = $this->repo->getScheduled($eventId, $this->batchLimit, $this->contactId);
        $this->scheduledContacts->hydrateContacts($logs);

        while ($logs->count()) {
            $event = $logs->first()->getEvent();
            $this->progressBar->advance($logs->count());
            $this->counter->advanceEvaluated($logs->count());

            // Validate that the schedule is still appropriate
            $this->validateSchedule($logs, $event, $now);

            // Execute if there are any that did not get rescheduled
            $this->executioner->executeLogs($event, $logs, $this->counter);

            // Get next batch
            $this->scheduledContacts->clear();
            $logs = $this->repo->getScheduled($eventId, $this->batchLimit, $this->contactId);
        }
    }

    /**
     * @param ArrayCollection $logs
     * @param Event           $event
     * @param \DateTime       $now
     *
     * @throws Scheduler\Exception\NotSchedulableException
     */
    private function validateSchedule(ArrayCollection $logs, Event $event, \DateTime $now)
    {
        // Check if the event should be scheduled (let the schedulers do the debug logging)
        /** @var LeadEventLog $log */
        foreach ($logs as $key => $log) {
            if ($createdDate = $log->getDateTriggered()) {
                // Date Triggered will be when the log entry was first created so use it to compare to ensure that the event's schedule
                // hasn't been changed since this event was first scheduled
                $executionDate = $this->scheduler->getExecutionDateTime($event, $now, $createdDate);
                $this->logger->debug(
                    'CAMPAIGN: Log ID# '.$log->getId().
                    ' to be executed on '.$executionDate->format('Y-m-d H:i:s').
                    ' compared to '.$now->format('Y-m-d H:i:s')
                );

                if ($executionDate > $now) {
                    // The schedule has changed for this event since first scheduled
                    $this->scheduler->reschedule($log, $executionDate);
                    $logs->remove($key);

                    continue;
                }
            }
        }
    }
}
