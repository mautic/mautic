<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\FailedLeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher;
use Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher;
use Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Executioner\Result\Responses;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated 2.13.0 to be removed in 3.0
 */
class LegacyEventModel extends CommonFormModel
{
    /**
     * @var RealTimeExecutioner
     */
    private $realTimeExecutioner;

    /**
     * @var KickoffExecutioner
     */
    private $kickoffExecutioner;

    /**
     * @var ScheduledExecutioner
     */
    private $scheduledExecutioner;

    /**
     * @var InactiveExecutioner
     */
    private $inactiveExecutioner;

    /**
     * @var EventExecutioner
     */
    private $eventExecutioner;

    /**
     * @var EventCollector
     */
    private $eventCollector;

    /**
     * @var ActionDispatcher
     */
    private $actionDispatcher;

    /**
     * @var ConditionDispatcher
     */
    private $conditionDispatcher;

    /**
     * @var DecisionDispatcher
     */
    private $decisionDispatcher;

    /**
     * @var
     */
    protected $triggeredEvents;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * @var LeadEventLogRepository
     */
    protected $leadEventLogRepository;

    /**
     * LegacyEventModel constructor.
     *
     * @param UserModel              $userModel
     * @param NotificationModel      $notificationModel
     * @param CampaignModel          $campaignModel
     * @param LeadModel              $leadModel
     * @param IpLookupHelper         $ipLookupHelper
     * @param RealTimeExecutioner    $realTimeExecutioner
     * @param KickoffExecutioner     $kickoffExecutioner
     * @param ScheduledExecutioner   $scheduledExecutioner
     * @param InactiveExecutioner    $inactiveExecutioner
     * @param EventExecutioner       $eventExecutioner
     * @param EventCollector         $eventCollector
     * @param ActionDispatcher       $actionDispatcher
     * @param ConditionDispatcher    $conditionDispatcher
     * @param DecisionDispatcher     $decisionDispatcher
     * @param LeadEventLogRepository $leadEventLogRepository
     */
    public function __construct(
        UserModel $userModel,
        NotificationModel $notificationModel,
        CampaignModel $campaignModel,
        LeadModel $leadModel,
        IpLookupHelper $ipLookupHelper,
        RealTimeExecutioner $realTimeExecutioner,
        KickoffExecutioner $kickoffExecutioner,
        ScheduledExecutioner $scheduledExecutioner,
        InactiveExecutioner $inactiveExecutioner,
        EventExecutioner $eventExecutioner,
        EventCollector $eventCollector,
        ActionDispatcher $actionDispatcher,
        ConditionDispatcher $conditionDispatcher,
        DecisionDispatcher $decisionDispatcher,
        LeadEventLogRepository $leadEventLogRepository
    ) {
        $this->userModel              = $userModel;
        $this->notificationModel      = $notificationModel;
        $this->campaignModel          = $campaignModel;
        $this->leadModel              = $leadModel;
        $this->ipLookupHelper         = $ipLookupHelper;
        $this->realTimeExecutioner    = $realTimeExecutioner;
        $this->kickoffExecutioner     = $kickoffExecutioner;
        $this->scheduledExecutioner   = $scheduledExecutioner;
        $this->inactiveExecutioner    = $inactiveExecutioner;
        $this->eventExecutioner       = $eventExecutioner;
        $this->eventCollector         = $eventCollector;
        $this->actionDispatcher       = $actionDispatcher;
        $this->conditionDispatcher    = $conditionDispatcher;
        $this->decisionDispatcher     = $decisionDispatcher;
        $this->leadEventLogRepository = $leadEventLogRepository;
    }

    /**
     * Trigger the root level action(s) in campaign(s).
     *
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param Campaign             $campaign
     * @param                      $totalEventCount
     * @param int                  $limit
     * @param bool                 $max
     * @param OutputInterface|null $output
     * @param null                 $leadId
     * @param bool                 $returnCounts
     *
     * @return array
     */
    public function triggerStartingEvents(
        Campaign $campaign,
        &$totalEventCount,
        $limit = 100,
        $max = false,
        OutputInterface $output = null,
        $leadId = null,
        $returnCounts = false
    ) {
        $limiter = new ContactLimiter($limit, $leadId, null, null);
        $counter = $this->kickoffExecutioner->execute($campaign, $limiter, $output);

        $totalEventCount += $counter->getEventCount();

        if ($returnCounts) {
            return [
                'events'         => $counter->getEventCount(),
                'evaluated'      => $counter->getEvaluated(),
                'executed'       => $counter->getExecuted(),
                'totalEvaluated' => $counter->getTotalEvaluated(),
                'totalExecuted'  => $counter->getTotalExecuted(),
            ];
        }

        return $counter->getTotalExecuted();
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param                      $campaign
     * @param                      $totalEventCount
     * @param int                  $limit
     * @param bool                 $max
     * @param OutputInterface|null $output
     * @param bool                 $returnCounts
     *
     * @return array
     */
    public function triggerScheduledEvents(
        $campaign,
        &$totalEventCount,
        $limit = 100,
        $max = false,
        OutputInterface $output = null,
        $returnCounts = false
    ) {
        $limiter = new ContactLimiter($limit, null, null, null);
        $counter = $this->scheduledExecutioner->execute($campaign, $limiter, $output);

        $totalEventCount += $counter->getEventCount();

        if ($returnCounts) {
            return [
                'events'         => $counter->getEventCount(),
                'evaluated'      => $counter->getEvaluated(),
                'executed'       => $counter->getExecuted(),
                'totalEvaluated' => $counter->getTotalEvaluated(),
                'totalExecuted'  => $counter->getTotalExecuted(),
            ];
        }

        return $counter->getTotalExecuted();
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param                      $campaign
     * @param int                  $totalEventCount
     * @param int                  $limit
     * @param bool                 $max
     * @param OutputInterface|null $output
     * @param bool                 $returnCounts
     *
     * @return array
     */
    public function triggerNegativeEvents(
        $campaign,
        &$totalEventCount = 0,
        $limit = 100,
        $max = false,
        OutputInterface $output = null,
        $returnCounts = false
    ) {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $limiter = new ContactLimiter($limit, null, null, null);
        $counter = $this->scheduledExecutioner->execute($campaign, $limiter, $output);

        $totalEventCount += $counter->getEventCount();

        if ($returnCounts) {
            return [
                'events'         => $counter->getEventCount(),
                'evaluated'      => $counter->getEvaluated(),
                'executed'       => $counter->getExecuted(),
                'totalEvaluated' => $counter->getTotalEvaluated(),
                'totalExecuted'  => $counter->getTotalExecuted(),
            ];
        }

        return $counter->getTotalExecuted();
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param      $type
     * @param null $eventDetails
     * @param null $channel
     * @param null $channelId
     *
     * @return array
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    public function triggerEvent($type, $eventDetails = null, $channel = null, $channelId = null)
    {
        $response = $this->realTimeExecutioner->execute($type, $eventDetails, $channel, $channelId);

        return $response->getResponseArray();
    }

    /**
     * Handles condition type events.
     *
     * @deprecated 2.6.0 to be removed in 3.0
     *
     * @param bool     $response
     * @param array    $eventSettings
     * @param array    $event
     * @param Campaign $campaign
     * @param Lead     $lead
     * @param int      $evaluatedEventCount The number of events evaluated for the current method (kickoff, negative/inaction, scheduled)
     * @param int      $executedEventCount  The number of events successfully executed for the current method
     * @param int      $totalEventCount     The total number of events across all methods
     *
     * @return bool True if an event was executed
     */
    public function handleCondition(
        $response,
        $eventSettings,
        $event,
        $campaign,
        $lead,
        &$evaluatedEventCount = 0,
        &$executedEventCount = 0,
        &$totalEventCount = 0
    ) {
        $repo         = $this->getRepository();
        $decisionPath = ($response === true) ? 'yes' : 'no';
        $childEvents  = $repo->getEventsByParent($event['id'], $decisionPath);

        $this->logger->debug(
            'CAMPAIGN: Condition ID# '.$event['id'].' triggered with '.$decisionPath.' decision path. Has '.count($childEvents).' child event(s).'
        );

        $childExecuted = false;
        foreach ($childEvents as $childEvent) {
            // Trigger child event recursively
            if ($this->executeEvent(
                $childEvent,
                $campaign,
                $lead,
                $eventSettings,
                true,
                null,
                null,
                false,
                $evaluatedEventCount,
                $executedEventCount,
                $totalEventCount
            )
            ) {
                $childExecuted = true;
            }
        }

        return $childExecuted;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param                   $event
     * @param                   $settings
     * @param null              $lead
     * @param null              $eventDetails
     * @param bool              $systemTriggered
     * @param LeadEventLog|null $log
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     */
    public function invokeEventCallback($event, $settings, $lead = null, $eventDetails = null, $systemTriggered = false, LeadEventLog $log = null)
    {
        if (is_array($event)) {
            /** @var Event $event */
            $event = $this->getEntity($event['id']);
        }

        $config = $this->eventCollector->getEventConfig($event);
        if (null === $log) {
            $log = $this->getLogEntity($event, $event->getCampaign(), $lead, null, $systemTriggered);
        }

        switch ($event->getEventType()) {
            case Event::TYPE_ACTION:
                $logs = new ArrayCollection([$log]);
                /* @var ActionAccessor $config */
                $this->actionDispatcher->dispatchEvent($config, $event, $logs);

                return !$log->getFailedLog();
            case Event::TYPE_CONDITION:
                /** @var ConditionAccessor $config */
                $eventResult = $this->conditionDispatcher->dispatchEvent($config, $log);

                return $eventResult->getResult();
            case Event::TYPE_DECISION:
                /** @var DecisionAccessor $config */
                $eventResult = $this->decisionDispatcher->dispatchEvent($config, $log, $eventDetails);

                return $eventResult->getResult();
        }
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param                $event
     * @param                $campaign
     * @param                $lead
     * @param null           $eventSettings
     * @param bool           $allowNegative
     * @param \DateTime|null $parentTriggeredDate
     * @param null           $eventTriggerDate
     * @param bool           $logExists
     * @param int            $evaluatedEventCount
     * @param int            $executedEventCount
     * @param int            $totalEventCount
     *
     * @return bool
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    public function executeEvent(
        $event,
        $campaign,
        $lead,
        $eventSettings = null,
        $allowNegative = false,
        \DateTime $parentTriggeredDate = null,
        $eventTriggerDate = null,
        $logExists = false,
        &$evaluatedEventCount = 0,
        &$executedEventCount = 0,
        &$totalEventCount = 0
    ) {
        $responses = new Responses();

        if (is_array($event)) {
            /** @var Event $event */
            $event = $this->getEntity($event['id']);
        }

        if ($logExists) {
            // Get a list of logs
            $scheduled = $this->leadEventLogRepository->findBy(
                [
                    'event'       => $event,
                    'lead'        => $lead,
                    'rotation'    => 1,
                    'isScheduled' => true,
                ]
            );

            $scheduledIds = [];
            /** @var LeadEventLog $log */
            foreach ($scheduled as $log) {
                $scheduledIds[] = $log->getId();
            }

            $counter = $this->scheduledExecutioner->executeByIds($scheduledIds);
        } else {
            $counter = new Counter();
            $this->eventExecutioner->executeForContact($event, $lead, $responses, $counter);
        }

        $evaluatedEventCount += $counter->getTotalEvaluated();
        $executedEventCount += $counter->getTotalExecuted();
        $totalEventCount += $counter->getEventCount();

        return (bool) $counter->getTotalExecuted();
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param Campaign $campaign
     * @param int      $evaluatedEventCount
     * @param int      $executedEventCount
     * @param int      $totalEventCount
     */
    public function triggerConditions(Campaign $campaign, &$evaluatedEventCount = 0, &$executedEventCount = 0, &$totalEventCount = 0)
    {
        $eventSettings   = $this->campaignModel->getEvents();
        $repo            = $this->getRepository();
        $sleepBatchCount = 0;
        $limit           = 100;

        while (!empty($this->triggeredEvents)) {
            // Reset the triggered events in order to be hydrated again for chained conditions/actions
            $triggeredEvents       = $this->triggeredEvents;
            $this->triggeredEvents = [];

            foreach ($triggeredEvents as $parentId => $decisionPaths) {
                foreach ($decisionPaths as $decisionPath => $contactIds) {
                    $typeRestriction = null;
                    if ('null' === $decisionPath) {
                        // This is an action so check if conditions are attached
                        $decisionPath    = null;
                        $typeRestriction = 'condition';
                    } // otherwise this should be a condition so get all children connected to the given path

                    $childEvents = $repo->getEventsByParent($parentId, $decisionPath, $typeRestriction);
                    $this->logger->debug(
                        'CAMPAIGN: Evaluating '.count($childEvents).' child event(s) to process the conditions of parent ID# '.$parentId.'.'
                    );

                    if (!count($childEvents)) {
                        continue;
                    }

                    $batchedContactIds = array_chunk($contactIds, $limit);
                    foreach ($batchedContactIds as $batchDebugCounter => $contactBatch) {
                        ++$batchDebugCounter; // start with 1

                        if (empty($contactBatch)) {
                            break;
                        }

                        $this->logger->debug('CAMPAIGN: Batch #'.$batchDebugCounter);

                        $leads = $this->leadModel->getEntities(
                            [
                                'filter'             => [
                                    'force' => [
                                        [
                                            'column' => 'l.id',
                                            'expr'   => 'in',
                                            'value'  => $contactBatch,
                                        ],
                                    ],
                                ],
                                'orderBy'            => 'l.id',
                                'orderByDir'         => 'asc',
                                'withPrimaryCompany' => true,
                            ]
                        );

                        $this->logger->debug('CAMPAIGN: Processing the following contacts: '.implode(', ', array_keys($leads)));

                        if (!count($leads)) {
                            // Just a precaution in case non-existent leads are lingering in the campaign leads table
                            $this->logger->debug('CAMPAIGN: No contact entities found.');

                            continue;
                        }

                        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
                        $leadDebugCounter = 0;
                        foreach ($leads as $lead) {
                            ++$leadDebugCounter; // start with 1

                            $this->logger->debug(
                                'CAMPAIGN: Current Lead ID# '.$lead->getId().'; #'.$leadDebugCounter.' in batch #'.$batchDebugCounter
                            );

                            // Set lead in case this is triggered by the system
                            $this->leadModel->setSystemCurrentLead($lead);

                            foreach ($childEvents as $childEvent) {
                                $this->executeEvent(
                                    $childEvent,
                                    $campaign,
                                    $lead,
                                    $eventSettings,
                                    true,
                                    null,
                                    null,
                                    false,
                                    $evaluatedEventCount,
                                    $executedEventCount,
                                    $totalEventCount
                                );

                                if ($sleepBatchCount == $limit) {
                                    // Keep CPU down
                                    $this->batchSleep();
                                    $sleepBatchCount = 0;
                                } else {
                                    ++$sleepBatchCount;
                                }
                            }
                        }

                        // Free RAM
                        $this->em->clear('Mautic\LeadBundle\Entity\Lead');
                        $this->em->clear('Mautic\UserBundle\Entity\User');
                        unset($leads);

                        // Free some memory
                        gc_collect_cycles();
                    }
                }
            }
        }
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param LeadEventLog $log
     * @param              $status
     * @param null         $reason
     */
    public function setEventStatus(LeadEventLog $log, $status, $reason = null)
    {
        $failedLog = $log->getFailedLog();

        if ($status) {
            if ($failedLog) {
                $this->em->getRepository('MauticCampaignBundle:FailedLeadEventLog')->deleteEntity($failedLog);
                $log->setFailedLog(null);
            }

            $metadata = $log->getMetadata();
            unset($metadata['errors']);
            $log->setMetadata($metadata);
        } else {
            if (!$failedLog) {
                $failedLog = new FailedLeadEventLog();
            }

            $failedLog->setDateAdded()
                ->setReason($reason)
                ->setLog($log);

            $this->em->persist($failedLog);
        }
    }

    /**
     * Check to see if the interval between events are appropriate to fire currentEvent.
     *
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param           $action
     * @param \DateTime $parentTriggeredDate
     * @param bool      $allowNegative
     *
     * @return bool
     */
    public function checkEventTiming($action, \DateTime $parentTriggeredDate = null, $allowNegative = false)
    {
        $now = new \DateTime();

        $this->logger->debug('CAMPAIGN: Check timing for '.ucfirst($action['eventType']).' ID# '.$action['id']);

        if ($action instanceof Event) {
            $action = $action->convertToArray();
        }

        if ($action['decisionPath'] == 'no' && !$allowNegative) {
            $this->logger->debug('CAMPAIGN: '.ucfirst($action['eventType']).' is attached to a negative path which is not allowed');

            return false;
        } else {
            $negate = ($action['decisionPath'] == 'no' && $allowNegative);

            if ($action['triggerMode'] == 'interval') {
                $triggerOn = $negate ? clone $parentTriggeredDate : new \DateTime();

                if ($triggerOn == null) {
                    $triggerOn = new \DateTime();
                }

                $interval = $action['triggerInterval'];
                $unit     = $action['triggerIntervalUnit'];

                $this->logger->debug('CAMPAIGN: Adding interval of '.$interval.$unit.' to '.$triggerOn->format('Y-m-d H:i:s T'));

                $triggerOn->add((new DateTimeHelper())->buildInterval($interval, $unit));

                if ($triggerOn > $now) {
                    $this->logger->debug(
                        'CAMPAIGN: Date to execute ('.$triggerOn->format('Y-m-d H:i:s T').') is later than ('.$now->format('Y-m-d H:i:s T')
                        .')'.(($action['decisionPath'] == 'no') ? ' so ignore' : ' so schedule')
                    );

                    // Save some RAM for batch processing
                    unset($now, $action, $dv, $dt);

                    //the event is to be scheduled based on the time interval
                    return $triggerOn;
                }
            } elseif ($action['triggerMode'] == 'date') {
                if (!$action['triggerDate'] instanceof \DateTime) {
                    $triggerDate           = new DateTimeHelper($action['triggerDate']);
                    $action['triggerDate'] = $triggerDate->getDateTime();
                    unset($triggerDate);
                }

                $this->logger->debug('CAMPAIGN: Date execution on '.$action['triggerDate']->format('Y-m-d H:i:s T'));

                $pastDue = $now >= $action['triggerDate'];

                if ($negate) {
                    $this->logger->debug(
                        'CAMPAIGN: Negative comparison; Date to execute ('.$action['triggerDate']->format('Y-m-d H:i:s T').') compared to now ('
                        .$now->format('Y-m-d H:i:s T').') and is thus '.(($pastDue) ? 'overdue' : 'not past due')
                    );

                    //it is past the scheduled trigger date and the lead has done nothing so return true to trigger
                    //the event otherwise false to do nothing
                    $return = ($pastDue) ? true : $action['triggerDate'];

                    // Save some RAM for batch processing
                    unset($now, $action);

                    return $return;
                } elseif (!$pastDue) {
                    $this->logger->debug(
                        'CAMPAIGN: Non-negative comparison; Date to execute ('.$action['triggerDate']->format('Y-m-d H:i:s T').') compared to now ('
                        .$now->format('Y-m-d H:i:s T').') and is thus not past due'
                    );

                    //schedule the event
                    return $action['triggerDate'];
                }
            }
        }

        $this->logger->debug('CAMPAIGN: Nothing stopped execution based on timing.');

        //default is to trigger the event
        return true;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param Event|int                                $event
     * @param Campaign                                 $campaign
     * @param \Mautic\LeadBundle\Entity\Lead|null      $lead
     * @param \Mautic\CoreBundle\Entity\IpAddress|null $ipAddress
     * @param bool                                     $systemTriggered
     *
     * @return LeadEventLog
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getLogEntity($event, $campaign, $lead = null, $ipAddress = null, $systemTriggered = false)
    {
        $log = new LeadEventLog();

        if ($ipAddress == null) {
            // Lead triggered from system IP
            $ipAddress = $this->ipLookupHelper->getIpAddress();
        }
        $log->setIpAddress($ipAddress);

        if (!$event instanceof Event) {
            $event = $this->em->getReference('MauticCampaignBundle:Event', $event);
        }
        $log->setEvent($event);

        if (!$campaign instanceof Campaign) {
            $campaign = $this->em->getReference('MauticCampaignBundle:Campaign', $campaign);
        }
        $log->setCampaign($campaign);

        if ($lead == null) {
            $lead = $this->leadModel->getCurrentLead();
        }
        $log->setLead($lead);
        $log->setDateTriggered(new \DateTime());
        $log->setSystemTriggered($systemTriggered);

        // Save some RAM for batch processing
        unset($event, $campaign, $lead);

        return $log;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param Lead $lead
     * @param      $campaignCreatedBy
     * @param      $header
     */
    public function notifyOfFailure(Lead $lead, $campaignCreatedBy, $header)
    {
        // Notify the lead owner if there is one otherwise campaign creator that there was a failure
        if (!$owner = $lead->getOwner()) {
            $ownerId = (int) $campaignCreatedBy;
            $owner   = $this->userModel->getEntity($ownerId);
        }

        if ($owner && $owner->getId()) {
            $this->notificationModel->addNotification(
                $header,
                'error',
                false,
                $this->translator->trans(
                    'mautic.campaign.event.failed',
                    [
                        '%contact%' => '<a href="'.$this->router->generate(
                                'mautic_contact_action',
                                ['objectAction' => 'view', 'objectId' => $lead->getId()]
                            ).'" data-toggle="ajax">'.$lead->getPrimaryIdentifier().'</a>',
                    ]
                ),
                null,
                null,
                $owner
            );
        }
    }

    /**
     * Batch sleep according to settings.
     *
     * @deprecated 2.13.0 to be removed in 3.0
     */
    protected function batchSleep()
    {
        // No longer used
    }
}
