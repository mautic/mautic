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
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\FailedLeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Exception\TypeNotFoundException;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber;
use Mautic\CampaignBundle\Executioner\Event\ActionExecutioner;
use Mautic\CampaignBundle\Executioner\Event\ConditionExecutioner;
use Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Mautic\CampaignBundle\Executioner\Result\Responses;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Helper\RemovedContactTracker;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\LoggerInterface;

class EventExecutioner
{
    /**
     * @var ActionExecutioner
     */
    private $actionExecutioner;

    /**
     * @var ConditionExecutioner
     */
    private $conditionExecutioner;

    /**
     * @var DecisionExecutioner
     */
    private $decisionExecutioner;

    /**
     * @var EventCollector
     */
    private $collector;

    /**
     * @var EventLogger
     */
    private $eventLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var Responses
     */
    private $responses;

    /**
     * @var RemovedContactTracker
     */
    private $removedContactTracker;

    /**
     * @var \DateTime
     */
    private $executionDate;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * EventExecutioner constructor.
     */
    public function __construct(
        EventCollector $eventCollector,
        EventLogger $eventLogger,
        ActionExecutioner $actionExecutioner,
        ConditionExecutioner $conditionExecutioner,
        DecisionExecutioner $decisionExecutioner,
        LoggerInterface $logger,
        EventScheduler $scheduler,
        RemovedContactTracker $removedContactTracker,
        LeadRepository $leadRepository
    ) {
        $this->actionExecutioner     = $actionExecutioner;
        $this->conditionExecutioner  = $conditionExecutioner;
        $this->decisionExecutioner   = $decisionExecutioner;
        $this->collector             = $eventCollector;
        $this->eventLogger           = $eventLogger;
        $this->logger                = $logger;
        $this->scheduler             = $scheduler;
        $this->removedContactTracker = $removedContactTracker;
        $this->leadRepository        = $leadRepository;

        // Be sure that all events are compared using the exact same \DateTime
        $this->executionDate = new \DateTime();
    }

    /**
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function executeForContact(Event $event, Lead $contact, Responses $responses = null, Counter $counter = null)
    {
        if ($responses) {
            $this->responses = $responses;
        }

        $contacts = new ArrayCollection([$contact->getId() => $contact]);

        $this->executeForContacts($event, $contacts, $counter);
    }

    /**
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function executeEventsForContact(ArrayCollection $events, Lead $contact, Responses $responses = null, Counter $counter = null)
    {
        if ($responses) {
            $this->responses = $responses;
        }

        $contacts = new ArrayCollection([$contact->getId() => $contact]);

        $this->executeEventsForContacts($events, $contacts, $counter);
    }

    /**
     * @param bool $isInactiveEvent
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function executeForContacts(Event $event, ArrayCollection $contacts, Counter $counter = null, $isInactiveEvent = false)
    {
        if (!$contacts->count()) {
            $this->logger->debug('CAMPAIGN: No contacts to process for event ID '.$event->getId());

            return;
        }

        $config = $this->collector->getEventConfig($event);
        $logs   = $this->eventLogger->fetchRotationAndGenerateLogsFromContacts($event, $config, $contacts, $isInactiveEvent);

        $this->executeLogs($event, $logs, $counter);
    }

    /**
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function executeLogs(Event $event, ArrayCollection $logs, Counter $counter = null)
    {
        $this->logger->debug('CAMPAIGN: Executing '.$event->getType().' ID '.$event->getId());

        if (!$logs->count()) {
            $this->logger->debug('CAMPAIGN: No logs to process for event ID '.$event->getId());

            return;
        }

        $config = $this->collector->getEventConfig($event);

        if ($counter) {
            // Must pass $counter around rather than setting it as a class property as this class is used
            // circularly to process children of parent events thus counter must be kept track separately
            $counter->advanceExecuted($logs->count());
        }

        switch ($event->getEventType()) {
            case Event::TYPE_ACTION:
                $evaluatedContacts = $this->actionExecutioner->execute($config, $logs);
                $this->persistLogs($logs);
                $this->executeConditionEventsForContacts($event, $evaluatedContacts->getPassed(), $counter);
                $this->executeActionEventsForContacts($event, $evaluatedContacts->getPassed(), $counter);
                break;
            case Event::TYPE_CONDITION:
                $evaluatedContacts = $this->conditionExecutioner->execute($config, $logs);
                $this->persistLogs($logs);
                $this->executeBranchedEventsForContacts($event, $evaluatedContacts, $counter);
                break;
            case Event::TYPE_DECISION:
                $evaluatedContacts = $this->decisionExecutioner->execute($config, $logs);
                $this->persistLogs($logs);
                $this->executePositivePathEventsForContacts($event, $evaluatedContacts->getPassed(), $counter);
                break;
            default:
                throw new TypeNotFoundException("{$event->getEventType()} is not a valid event type");
        }
    }

    /**
     * @param bool $isInactive
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function executeEventsForContacts(ArrayCollection $events, ArrayCollection $contacts, Counter $childrenCounter = null, $isInactive = false)
    {
        if (!$contacts->count()) {
            return;
        }

        // Schedule then return those that need to be immediately executed
        $executeThese = $this->scheduleEvents($events, $contacts, $childrenCounter, $isInactive);

        // Execute non jump-to events normally
        $otherEvents = $executeThese->filter(function (Event $event) {
            return CampaignActionJumpToEventSubscriber::EVENT_NAME !== $event->getType();
        });

        if ($otherEvents->count()) {
            foreach ($otherEvents as $event) {
                $this->executeForContacts($event, $contacts, $childrenCounter, $isInactive);
            }
        }

        // Now execute jump to events
        $jumpEvents = $executeThese->filter(function (Event $event) {
            return CampaignActionJumpToEventSubscriber::EVENT_NAME === $event->getType();
        });
        if ($jumpEvents->count()) {
            $jumpLogs = [];

            // Create logs for the jump to events before the rotation is incremented
            foreach ($jumpEvents as $key => $event) {
                $config         = $this->collector->getEventConfig($event);
                $jumpLogs[$key] = $this->eventLogger->fetchRotationAndGenerateLogsFromContacts($event, $config, $contacts, $isInactive);
            }

            // Increment the campaign rotation for the given contacts and current campaign
            $this->leadRepository->incrementCampaignRotationForContacts(
                $contacts->getKeys(),
                $jumpEvents->first()->getCampaign()->getId()
            );

            // Process the jump to events
            foreach ($jumpLogs as $key => $logs) {
                $this->executeLogs($jumpEvents->get($key), $logs, $childrenCounter);
            }
        }
    }

    /**
     * @param bool $isInactiveEvent
     */
    public function recordLogsAsExecutedForEvent(Event $event, ArrayCollection $contacts, $isInactiveEvent = false)
    {
        $config = $this->collector->getEventConfig($event);
        $logs   = $this->eventLogger->generateLogsFromContacts($event, $config, $contacts, $isInactiveEvent);

        // Save updated log entries and clear from memory
        $this->eventLogger->persistCollection($logs)
            ->clearCollection($logs);
    }

    /**
     * @param      $reason
     * @param bool $isInactiveEvent
     */
    public function recordLogsAsFailedForEvent(Event $event, ArrayCollection $contacts, $reason, $isInactiveEvent = false)
    {
        $config = $this->collector->getEventConfig($event);
        $logs   = $this->eventLogger->generateLogsFromContacts($event, $config, $contacts, $isInactiveEvent);

        foreach ($logs as $log) {
            $failedLog = new FailedLeadEventLog();
            $failedLog->setLog($log)
                ->setReason($reason);
        }

        // Save updated log entries and clear from memory
        $this->eventLogger->persistCollection($logs)
            ->clear();
    }

    /**
     * @param ArrayCollection|LeadEventLog[] $logs
     * @param string                         $error
     */
    public function recordLogsWithError(ArrayCollection $logs, $error)
    {
        foreach ($logs as $log) {
            $log->appendToMetadata(
                [
                    'failed' => 1,
                    'reason' => $error,
                ]
            );

            $log->setIsScheduled(false);
        }

        // Save updated log entries and clear from memory
        $this->eventLogger->persistCollection($logs)
            ->clear();
    }

    /**
     * @return \DateTime
     */
    public function getExecutionDate()
    {
        return $this->executionDate;
    }

    /**
     * @param bool $isInactive
     *
     * @return ArrayCollection
     *
     * @throws Scheduler\Exception\NotSchedulableException
     */
    private function scheduleEvents(ArrayCollection $events, ArrayCollection $contacts, Counter $childrenCounter = null, $isInactive = false)
    {
        $events = clone $events;

        foreach ($events as $key => $event) {
            // Ignore decisions
            if (Event::TYPE_DECISION == $event->getEventType()) {
                $this->logger->debug('CAMPAIGN: Ignoring child event ID '.$event->getId().' as a decision');
                continue;
            }

            $executionDate = $this->scheduler->getExecutionDateTime($event, $this->executionDate);

            $this->logger->debug(
                'CAMPAIGN: Event ID# '.$event->getId().
                ' to be executed on '.$executionDate->format('Y-m-d H:i:s e')
            );

            // Check if we need to schedule this if it is not an inactivity check
            if (!$isInactive && $this->scheduler->shouldScheduleEvent($event, $executionDate, $this->executionDate)) {
                if ($childrenCounter) {
                    $childrenCounter->advanceTotalScheduled($contacts->count());
                }

                $this->scheduler->schedule($event, $executionDate, $contacts, $isInactive);

                $events->remove($key);

                continue;
            }
        }

        return $events;
    }

    private function persistLogs(ArrayCollection $logs)
    {
        if ($this->responses) {
            // Extract responses
            $this->responses->setFromLogs($logs);
        }

        $this->checkForRemovedContacts($logs);

        // Save updated log entries and clear from memory
        $this->eventLogger->persistCollection($logs)
            ->clearCollection($logs);
    }

    private function checkForRemovedContacts(ArrayCollection $logs)
    {
        /**
         * @var int
         * @var LeadEventLog $log
         */
        foreach ($logs as $key => $log) {
            // Use the deleted ID if the contact was removed by the delete contact action
            $contact    = $log->getLead();
            $contactId  = (!empty($contact->deletedId)) ? $contact->deletedId : $contact->getId();
            $campaignId = $log->getCampaign()->getId();

            if ($this->removedContactTracker->wasContactRemoved($campaignId, $contactId)) {
                $this->logger->debug("CAMPAIGN: Contact ID# $contactId has been removed from campaign ID $campaignId");
                $logs->remove($key);

                // Clear out removed contacts to prevent a memory leak
                $this->removedContactTracker->clearRemovedContact($campaignId, $contactId);
            }
        }
    }

    /**
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    private function executeActionEventsForContacts(Event $event, ArrayCollection $contacts, Counter $counter = null)
    {
        $childrenCounter = new Counter();
        $actions         = $event->getChildrenByEventType(Event::TYPE_ACTION);
        $childrenCounter->advanceEvaluated($actions->count());

        $this->logger->debug('CAMPAIGN: Executing '.$actions->count().' actions under action ID '.$event->getId());

        $this->executeEventsForContacts($actions, $contacts, $childrenCounter);

        if ($counter) {
            $counter->advanceTotalEvaluated($childrenCounter->getTotalEvaluated());
            $counter->advanceTotalExecuted($childrenCounter->getTotalExecuted());
        }
    }

    /**
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    private function executeConditionEventsForContacts(Event $event, ArrayCollection $contacts, Counter $counter = null)
    {
        $childrenCounter = new Counter();
        $conditions      = $event->getChildrenByEventType(Event::TYPE_CONDITION);
        $childrenCounter->advanceEvaluated($conditions->count());

        $this->logger->debug('CAMPAIGN: Evaluating '.$conditions->count().' conditions for action ID '.$event->getId());

        $this->executeEventsForContacts($conditions, $contacts, $childrenCounter);

        if ($counter) {
            $counter->advanceTotalEvaluated($childrenCounter->getTotalEvaluated());
            $counter->advanceTotalExecuted($childrenCounter->getTotalExecuted());
        }
    }

    /**
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    private function executeBranchedEventsForContacts(Event $event, EvaluatedContacts $contacts, Counter $counter = null)
    {
        $childrenCounter = new Counter();
        $this->executePositivePathEventsForContacts($event, $contacts->getPassed(), $childrenCounter);
        $this->executeNegativePathEventsForContacts($event, $contacts->getFailed(), $childrenCounter);

        if ($counter) {
            $counter->advanceTotalEvaluated($childrenCounter->getTotalEvaluated());
            $counter->advanceTotalExecuted($childrenCounter->getTotalExecuted());
        }
    }

    /**
     * @param Counter|null $counter
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    private function executePositivePathEventsForContacts(Event $event, ArrayCollection $contacts, Counter $counter)
    {
        if (!$contacts->count()) {
            return;
        }

        $this->logger->debug('CAMPAIGN: Contact IDs '.implode(',', $contacts->getKeys()).' passed evaluation for event ID '.$event->getId());

        $children = $event->getPositiveChildren();
        $counter->advanceEvaluated($children->count());

        $this->executeEventsForContacts($children, $contacts, $counter);
    }

    /**
     * @param Counter|null $counter
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    private function executeNegativePathEventsForContacts(Event $event, ArrayCollection $contacts, Counter $counter)
    {
        if (!$contacts->count()) {
            return;
        }

        $this->logger->debug('CAMPAIGN: Contact IDs '.implode(',', $contacts->getKeys()).' failed evaluation for event ID '.$event->getId());

        $children = $event->getNegativeChildren();
        $counter->advanceEvaluated($children->count());

        $this->executeEventsForContacts($children, $contacts, $counter);
    }
}
