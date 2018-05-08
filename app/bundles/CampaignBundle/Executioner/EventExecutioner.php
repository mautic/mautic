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
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Exception\TypeNotFoundException;
use Mautic\CampaignBundle\EventCollector\EventCollector;
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
     * EventExecutioner constructor.
     *
     * @param EventCollector       $eventCollector
     * @param EventLogger          $eventLogger
     * @param ActionExecutioner    $actionExecutioner
     * @param ConditionExecutioner $conditionExecutioner
     * @param DecisionExecutioner  $decisionExecutioner
     * @param LoggerInterface      $logger
     * @param EventScheduler       $scheduler
     */
    public function __construct(
        EventCollector $eventCollector,
        EventLogger $eventLogger,
        ActionExecutioner $actionExecutioner,
        ConditionExecutioner $conditionExecutioner,
        DecisionExecutioner $decisionExecutioner,
        LoggerInterface $logger,
        EventScheduler $scheduler,
        RemovedContactTracker $removedContactTracker
    ) {
        $this->actionExecutioner     = $actionExecutioner;
        $this->conditionExecutioner  = $conditionExecutioner;
        $this->decisionExecutioner   = $decisionExecutioner;
        $this->collector             = $eventCollector;
        $this->eventLogger           = $eventLogger;
        $this->logger                = $logger;
        $this->scheduler             = $scheduler;
        $this->removedContactTracker = $removedContactTracker;

        // Be sure that all events are compared using the exact same \DateTime
        $this->executionDate = new \DateTime();
    }

    /**
     * @param Event          $event
     * @param Lead           $contact
     * @param Responses|null $responses
     * @param Counter|null   $counter
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function executeForContact(Event $event, Lead $contact, Responses $responses = null, Counter $counter = null)
    {
        $this->responses = $responses;

        $contacts = new ArrayCollection([$contact->getId() => $contact]);

        $this->executeForContacts($event, $contacts, $counter);
    }

    /**
     * @param Event           $event
     * @param ArrayCollection $contacts
     * @param Counter|null    $counter
     * @param bool            $validatingInaction
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
        $logs   = $this->eventLogger->generateLogsFromContacts($event, $config, $contacts, $isInactiveEvent);

        $this->executeLogs($event, $logs, $counter);
    }

    /**
     * @param Event           $event
     * @param ArrayCollection $logs
     * @param Counter|null    $counter
     *
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

        $this->checkForRemovedContacts($logs);

        if ($counter) {
            // Must pass $counter around rather than setting it as a class property as this class is used
            // circularly to process children of parent events thus counter must be kept track separately
            $counter->advanceExecuted($logs->count());
        }

        switch ($event->getEventType()) {
            case Event::TYPE_ACTION:
                $evaluatedContacts = $this->actionExecutioner->execute($config, $logs);
                $this->persistLogs($logs);
                $this->executeEventConditionsForContacts($event, $evaluatedContacts->getPassed(), $counter);
                break;
            case Event::TYPE_CONDITION:
                $evaluatedContacts = $this->conditionExecutioner->execute($config, $logs);
                $this->persistLogs($logs);
                $this->executeDecisionPathEventsForContacts($event, $evaluatedContacts, $counter);
                break;
            case Event::TYPE_DECISION:
                $evaluatedContacts = $this->decisionExecutioner->execute($config, $logs);
                $this->persistLogs($logs);
                $this->executeDecisionPathEventsForContacts($event, $evaluatedContacts, $counter);
                break;
            default:
                throw new TypeNotFoundException("{$event->getEventType()} is not a valid event type");
        }
    }

    /**
     * @param ArrayCollection $children
     * @param ArrayCollection $contacts
     * @param Counter         $childrenCounter
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function executeEventsForContacts(ArrayCollection $events, ArrayCollection $contacts, Counter $childrenCounter)
    {
        if (!$contacts->count()) {
            return;
        }

        foreach ($events as $event) {
            // Ignore decisions
            if (Event::TYPE_DECISION == $event->getEventType()) {
                $this->logger->debug('CAMPAIGN: Ignoring child event ID '.$event->getId().' as a decision');
                continue;
            }

            $executionDate = $this->scheduler->getExecutionDateTime($event, $this->executionDate);

            $this->logger->debug(
                'CAMPAIGN: Event ID# '.$event->getId().
                ' to be executed on '.$executionDate->format('Y-m-d H:i:s')
            );

            if ($this->scheduler->shouldSchedule($executionDate, $this->executionDate)) {
                $childrenCounter->advanceTotalScheduled($contacts->count());
                $this->scheduler->schedule($event, $executionDate, $contacts);
                continue;
            }

            $this->executeForContacts($event, $contacts, $childrenCounter);
        }
    }

    /**
     * @param Event           $event
     * @param ArrayCollection $contacts
     * @param bool            $isInactiveEvent
     */
    public function recordLogsAsExecutedForEvent(Event $event, ArrayCollection $contacts, $isInactiveEvent = false)
    {
        $config = $this->collector->getEventConfig($event);
        $logs   = $this->eventLogger->generateLogsFromContacts($event, $config, $contacts, $isInactiveEvent);

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
     * @param ArrayCollection $logs
     */
    private function persistLogs(ArrayCollection $logs)
    {
        if ($this->responses) {
            // Extract responses
            $this->responses->setFromLogs($logs);
        }

        // Save updated log entries and clear from memory
        $this->eventLogger->persistCollection($logs)
            ->clear();
    }

    /**
     * @param ArrayCollection $logs
     */
    private function checkForRemovedContacts(ArrayCollection $logs)
    {
        /**
         * @var int
         * @var LeadEventLog $log
         */
        foreach ($logs as $key => $log) {
            $contactId  = $log->getLead()->getId();
            $campaignId = $log->getCampaign()->getId();

            if ($this->removedContactTracker->wasContactRemoved($campaignId, $contactId)) {
                $this->logger->debug("CAMPAIGN: Contact ID# $contactId has been removed from campaign ID $campaignId");
                $logs->remove($key);
            }
        }
    }

    /**
     * @param Event           $event
     * @param ArrayCollection $contacts
     * @param Counter|null    $counter
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    private function executeEventConditionsForContacts(Event $event, ArrayCollection $contacts, Counter $counter = null)
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
     * @param Event             $event
     * @param EvaluatedContacts $contacts
     * @param Counter|null      $counter
     */
    private function executeDecisionPathEventsForContacts(Event $event, EvaluatedContacts $contacts, Counter $counter = null)
    {
        $childrenCounter = new Counter();
        $positive        = $contacts->getPassed();
        if ($positive->count()) {
            $this->logger->debug('CAMPAIGN: Contact IDs '.implode(',', $positive->getKeys()).' passed evaluation for event ID '.$event->getId());

            $children = $event->getPositiveChildren();
            $childrenCounter->advanceEvaluated($children->count());
            $this->executeEventsForContacts($children, $positive, $childrenCounter);
        }

        $negative = $contacts->getFailed();
        if ($negative->count()) {
            $this->logger->debug('CAMPAIGN: Contact IDs '.implode(',', $negative->getKeys()).' failed evaluation for event ID '.$event->getId());

            $children = $event->getNegativeChildren();

            $childrenCounter->advanceEvaluated($children->count());
            $this->executeEventsForContacts($children, $negative, $childrenCounter);
        }

        if ($counter) {
            $counter->advanceTotalEvaluated($childrenCounter->getTotalEvaluated());
            $counter->advanceTotalExecuted($childrenCounter->getTotalExecuted());
        }
    }
}
