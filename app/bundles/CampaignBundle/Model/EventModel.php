<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityNotFoundException;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\CampaignDecisionEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\CampaignScheduledEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EventModel
 * {@inheritdoc}
 */
class EventModel extends CommonFormModel
{
    /**
     * @var mixed
     */
    protected $batchSleepTime;

    /**
     * @var mixed
     */
    protected $batchCampaignSleepTime;

    /**
     * Used in triggerEvent so that responses from recursive events are saved.
     *
     * @var bool
     */
    private $triggeredResponses = false;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * @var mixed
     */
    protected $scheduleTimeForFailedEvents;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * Track triggered events to check for conditions that may be attached.
     *
     * @var array
     */
    protected $triggeredEvents = [];

    /**
     * EventModel constructor.
     *
     * @param IpLookupHelper       $ipLookupHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param LeadModel            $leadModel
     * @param CampaignModel        $campaignModel
     * @param UserModel            $userModel
     * @param NotificationModel    $notificationModel
     * @param MauticFactory        $factory
     */
    public function __construct(
        IpLookupHelper $ipLookupHelper,
        CoreParametersHelper $coreParametersHelper,
        LeadModel $leadModel,
        CampaignModel $campaignModel,
        UserModel $userModel,
        NotificationModel $notificationModel,
        MauticFactory $factory
    ) {
        $this->ipLookupHelper              = $ipLookupHelper;
        $this->leadModel                   = $leadModel;
        $this->campaignModel               = $campaignModel;
        $this->userModel                   = $userModel;
        $this->notificationModel           = $notificationModel;
        $this->batchSleepTime              = $coreParametersHelper->getParameter('mautic.batch_sleep_time');
        $this->batchCampaignSleepTime      = $coreParametersHelper->getParameter('mautic.batch_campaign_sleep_time');
        $this->scheduleTimeForFailedEvents = $coreParametersHelper->getParameter('campaign_time_wait_on_event_false');
        $this->factory                     = $factory;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\EventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Event');
    }

    /**
     * Get CampaignRepository.
     *
     * @return \Mautic\CampaignBundle\Entity\CampaignRepository
     */
    public function getCampaignRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Campaign');
    }

    /**
     * @return LeadEventLogRepository
     */
    public function getLeadEventLogRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:LeadEventLog');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'campaign:campaigns';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Event();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * Delete events.
     *
     * @param $currentEvents
     * @param $deletedEvents
     */
    public function deleteEvents($currentEvents, $deletedEvents)
    {
        $deletedKeys = [];
        foreach ($deletedEvents as $k => $deleteMe) {
            if ($deleteMe instanceof Event) {
                $deleteMe = $deleteMe->getId();
            }

            if (strpos($deleteMe, 'new') === 0) {
                unset($deletedEvents[$k]);
            }

            if (isset($currentEvents[$deleteMe])) {
                unset($deletedEvents[$k]);
            }

            $deletedKeys[] = $deleteMe;
        }

        // wipe out any references to these events to prevent restraint violations
        $this->getRepository()->nullEventRelationships($deletedKeys);

        // delete the events
        $this->deleteEntities($deletedEvents);
    }

    /**
     * Triggers an event.
     *
     * @param      $type
     * @param null $eventDetails
     * @param null $channel
     * @param null $channelId
     *
     * @return array|bool
     */
    public function triggerEvent($type, $eventDetails = null, $channel = null, $channelId = null)
    {
        static $leadCampaigns = [], $eventList = [], $availableEventSettings = [], $leadsEvents = [], $examinedEvents = [];

        $this->logger->debug('CAMPAIGN: Campaign triggered for event type '.$type.'('.$channel.' / '.$channelId.')');

        // Skip the anonymous check to force actions to fire for subsequent triggers
        $systemTriggered = defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED');

        if (!$systemTriggered) {
            defined('MAUTIC_CAMPAIGN_NOT_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_NOT_SYSTEM_TRIGGERED', 1);
        }

        //only trigger events for anonymous users (to prevent populating full of user/company data)
        /*if (!$systemTriggered && !$this->security->isAnonymous()) {
            $this->logger->debug('CAMPAIGN: contact not anonymous; abort');

            return false;
        }*/

        //get the current lead
        $lead   = $this->leadModel->getCurrentLead();
        $leadId = $lead->getId();
        $this->logger->debug('CAMPAIGN: Current Lead ID# '.$leadId);

        //get the lead's campaigns so we have when the lead was added
        if (empty($leadCampaigns[$leadId])) {
            $leadCampaigns[$leadId] = $this->campaignModel->getLeadCampaigns($lead, true);
        }

        if (empty($leadCampaigns[$leadId])) {
            $this->logger->debug('CAMPAIGN: no campaigns found so abort');

            return false;
        }

        //get the list of events that match the triggering event and is in the campaigns this lead belongs to
        /** @var \Mautic\CampaignBundle\Entity\EventRepository $eventRepo */
        $eventRepo = $this->getRepository();
        if (empty($eventList[$leadId][$type])) {
            $eventList[$leadId][$type] = $eventRepo->getPublishedByType($type, $leadCampaigns[$leadId], $lead->getId());
        }
        $events = $eventList[$leadId][$type];
        //get event settings from the bundles
        if (empty($availableEventSettings)) {
            $availableEventSettings = $this->campaignModel->getEvents();
        }
        //make sure there are events before continuing
        if (!count($availableEventSettings) || empty($events)) {
            $this->logger->debug('CAMPAIGN: no events found so abort');

            return false;
        }

        //get campaign list
        $campaigns = $this->campaignModel->getEntities(
            [
                'force' => [
                    'filter' => [
                        [
                            'column' => 'c.id',
                            'expr'   => 'in',
                            'value'  => array_keys($events),
                        ],
                    ],
                ],
                'ignore_paginator' => true,
            ]
        );

        //get a list of events that has already been executed for this lead
        if (empty($leadsEvents[$leadId])) {
            $leadsEvents[$leadId] = $eventRepo->getLeadTriggeredEvents($leadId);
        }

        if (!isset($examinedEvents[$leadId])) {
            $examinedEvents[$leadId] = [];
        }

        $this->triggeredResponses = [];
        $logs                     = [];
        foreach ($events as $campaignId => $campaignEvents) {
            if (empty($campaigns[$campaignId])) {
                $this->logger->debug('CAMPAIGN: Campaign entity for ID# '.$campaignId.' not found');

                continue;
            }

            foreach ($campaignEvents as $k => $event) {
                //has this event already been examined via a parent's children?
                //all events of this triggering type has to be queried since this particular event could be anywhere in the dripflow
                if (in_array($event['id'], $examinedEvents[$leadId])) {
                    $this->logger->debug('CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' already processed this round');
                    continue;
                }
                $examinedEvents[$leadId][] = $event['id'];

                //check to see if this has been fired sequentially
                if (!empty($event['parent'])) {
                    if (!isset($leadsEvents[$leadId][$event['parent']['id']])) {
                        //this event has a parent that has not been triggered for this lead so break out
                        $this->logger->debug(
                            'CAMPAIGN: parent (ID# '.$event['parent']['id'].') for ID# '.$event['id']
                            .' has not been triggered yet or was triggered with this batch'
                        );
                        continue;
                    }
                    $parentLog = $leadsEvents[$leadId][$event['parent']['id']]['log'][0];

                    if ($parentLog['isScheduled']) {
                        //this event has a parent that is scheduled and thus not triggered
                        $this->logger->debug(
                            'CAMPAIGN: parent (ID# '.$event['parent']['id'].') for ID# '.$event['id']
                            .' has not been triggered yet because it\'s scheduled'
                        );
                        continue;
                    } else {
                        $parentTriggeredDate = $parentLog['dateTriggered'];
                    }
                } else {
                    $parentTriggeredDate = new \DateTime();
                }

                if (isset($availableEventSettings[$event['eventType']][$type])) {
                    $decisionEventSettings = $availableEventSettings[$event['eventType']][$type];
                } else {
                    // Not found maybe it's no longer available?
                    $this->logger->debug('CAMPAIGN: '.$type.' does not exist. (#'.$event['id'].')');

                    continue;
                }

                //check the callback function for the event to make sure it even applies based on its settings
                if (!$response = $this->invokeEventCallback($event, $decisionEventSettings, $lead, $eventDetails, $systemTriggered)) {
                    $this->logger->debug(
                        'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' callback check failed with a response of '.var_export(
                            $response,
                            true
                        )
                    );

                    continue;
                }

                if (!empty($event['children'])) {
                    $this->logger->debug('CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' has children');

                    $childrenTriggered = false;
                    foreach ($event['children'] as $child) {
                        if (isset($leadsEvents[$leadId][$child['id']])) {
                            //this child event has already been fired for this lead so move on to the next event
                            $this->logger->debug('CAMPAIGN: '.ucfirst($child['eventType']).' ID# '.$child['id'].' already triggered');
                            continue;
                        } elseif ($child['eventType'] == 'decision') {
                            //hit a triggering type event so move on
                            $this->logger->debug('CAMPAIGN: ID# '.$child['id'].' is a decision');

                            continue;
                        } elseif ($child['decisionPath'] == 'no') {
                            // non-action paths should not be processed by this because the contact already took action in order to get here
                            $childrenTriggered = true;
                        } else {
                            $this->logger->debug('CAMPAIGN: '.ucfirst($child['eventType']).' ID# '.$child['id'].' is being processed');
                        }

                        //store in case a child was pulled with events
                        $examinedEvents[$leadId][] = $child['id'];

                        if ($this->executeEvent($child, $campaigns[$campaignId], $lead, $availableEventSettings, false, $parentTriggeredDate)) {
                            $childrenTriggered = true;
                        }
                    }

                    if ($childrenTriggered) {
                        $this->logger->debug('CAMPAIGN: Decision ID# '.$event['id'].' successfully executed and logged.');

                        //a child of this event was triggered or scheduled so make not of the triggering event in the log
                        $log = $this->getLogEntity($event['id'], $campaigns[$campaignId], $lead, null, $systemTriggered);
                        $log->setChannel($channel)
                            ->setChannelId($channelId);
                        $logs[] = $log;
                    } else {
                        $this->logger->debug('CAMPAIGN: Decision not logged');
                    }
                } else {
                    $this->logger->debug('CAMPAIGN: No children for this event.');
                }
            }

            $this->triggerConditions($campaigns[$campaignId]);
        }

        if (count($logs)) {
            $this->getLeadEventLogRepository()->saveEntities($logs);
        }

        if ($lead->getChanges()) {
            $this->leadModel->saveEntity($lead, false);
        }

        if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_DECISION_TRIGGER)) {
            $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_DECISION_TRIGGER,
                new CampaignDecisionEvent($lead, $type, $eventDetails, $events, $availableEventSettings, false, $logs)
            );
        }

        $actionResponses          = $this->triggeredResponses;
        $this->triggeredResponses = false;

        return $actionResponses;
    }

    /**
     * Trigger the root level action(s) in campaign(s).
     *
     * @param Campaign        $campaign
     * @param                 $totalEventCount
     * @param int             $limit
     * @param bool            $max
     * @param OutputInterface $output
     * @param int|null        $leadId
     * @param bool|false      $returnCounts    If true, returns array of counters
     *
     * @return int
     */
    public function triggerStartingEvents(
        $campaign,
        &$totalEventCount,
        $limit = 100,
        $max = false,
        OutputInterface $output = null,
        $leadId = null,
        $returnCounts = false
    ) {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $campaignId = $campaign->getId();

        $this->logger->debug('CAMPAIGN: Triggering starting events');

        $repo         = $this->getRepository();
        $campaignRepo = $this->getCampaignRepository();
        $logRepo      = $this->getLeadEventLogRepository();

        if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_DECISION_TRIGGER)) {
            // Include decisions if there are listeners
            $events = $repo->getRootLevelEvents($campaignId, true, true);

            // Filter out decisions
            $decisionChildren = [];
            foreach ($events as $event) {
                if ($event['eventType'] == 'decision') {
                    $decisionChildren[$event['id']] = $repo->getEventsByParent($event['id']);
                }
            }
        } else {
            $events = $repo->getRootLevelEvents($campaignId);
        }

        $rootEventCount = count($events);

        if (empty($rootEventCount)) {
            $this->logger->debug('CAMPAIGN: No events to trigger');

            return ($returnCounts) ? [
                'events'         => 0,
                'evaluated'      => 0,
                'executed'       => 0,
                'totalEvaluated' => 0,
                'totalExecuted'  => 0,
            ] : 0;
        }

        // Event settings
        $eventSettings = $this->campaignModel->getEvents();

        // Get a lead count; if $leadId, then use this as a check to ensure lead is part of the campaign
        $leadCount = $campaignRepo->getCampaignLeadCount($campaignId, $leadId, array_keys($events));

        // Get a total number of events that will be processed
        $totalStartingEvents = $leadCount * $rootEventCount;

        if ($output) {
            $output->writeln(
                $this->translator->trans(
                    'mautic.campaign.trigger.event_count',
                    ['%events%' => $totalStartingEvents, '%batch%' => $limit]
                )
            );
        }

        if (empty($leadCount)) {
            $this->logger->debug('CAMPAIGN: No contacts to process');

            unset($events);

            return ($returnCounts) ? [
                'events'         => 0,
                'evaluated'      => 0,
                'executed'       => 0,
                'totalEvaluated' => 0,
                'totalExecuted'  => 0,
            ] : 0;
        }

        $evaluatedEventCount = $executedEventCount = $rootEvaluatedCount = $rootExecutedCount = 0;

        // Try to save some memory
        gc_enable();

        $maxCount = ($max) ? $max : $totalStartingEvents;

        if ($output) {
            $progress = ProgressBarHelper::init($output, $maxCount);
            $progress->start();
        }

        $continue = true;

        $sleepBatchCount   = 0;
        $batchDebugCounter = 1;

        $this->logger->debug('CAMPAIGN: Processing the following events: '.implode(', ', array_keys($events)));

        while ($continue) {
            $this->logger->debug('CAMPAIGN: Batch #'.$batchDebugCounter);

            // Get list of all campaign leads; start is always zero in practice because of $pendingOnly
            $campaignLeads = ($leadId) ? [$leadId] : $campaignRepo->getCampaignLeadIds($campaignId, 0, $limit, true);

            if (empty($campaignLeads)) {
                // No leads found
                $this->logger->debug('CAMPAIGN: No campaign contacts found.');

                break;
            }

            $leads = $this->leadModel->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $campaignLeads,
                            ],
                        ],
                    ],
                    'orderBy'            => 'l.id',
                    'orderByDir'         => 'asc',
                    'withPrimaryCompany' => true,
                    'withChannelRules'   => true,
                ]
            );

            $this->logger->debug('CAMPAIGN: Processing the following contacts: '.implode(', ', array_keys($leads)));

            if (!count($leads)) {
                // Just a precaution in case non-existent leads are lingering in the campaign leads table
                $this->logger->debug('CAMPAIGN: No contact entities found.');

                break;
            }

            /** @var \Mautic\LeadBundle\Entity\Lead $lead */
            $leadDebugCounter = 1;
            foreach ($leads as $lead) {
                $this->logger->debug('CAMPAIGN: Current Lead ID# '.$lead->getId().'; #'.$leadDebugCounter.' in batch #'.$batchDebugCounter);

                if ($rootEvaluatedCount >= $maxCount || ($max && ($rootEvaluatedCount + $rootEventCount) >= $max)) {
                    // Hit the max or will hit the max mid-progress for a lead
                    $continue = false;
                    $this->logger->debug('CAMPAIGN: Hit max so aborting.');

                    break;
                }

                // Set lead in case this is triggered by the system
                $this->leadModel->setSystemCurrentLead($lead);

                foreach ($events as $event) {
                    ++$rootEvaluatedCount;

                    if ($sleepBatchCount == $limit) {
                        // Keep CPU down
                        $this->batchSleep();
                        $sleepBatchCount = 0;
                    } else {
                        ++$sleepBatchCount;
                    }

                    if ($event['eventType'] == 'decision') {
                        ++$evaluatedEventCount;
                        ++$totalEventCount;

                        $event['campaign'] = [
                            'id'   => $campaign->getId(),
                            'name' => $campaign->getName(),
                        ];

                        $decisionEvent = [
                            $campaignId => [
                                array_merge(
                                    $event,
                                    ['children' => $decisionChildren[$event['id']]]
                                ),
                            ],
                        ];
                        $decisionTriggerEvent = new CampaignDecisionEvent($lead, $event['type'], null, $decisionEvent, $eventSettings, true);
                        $this->dispatcher->dispatch(
                            CampaignEvents::ON_EVENT_DECISION_TRIGGER,
                            $decisionTriggerEvent
                        );
                        if ($decisionTriggerEvent->wasDecisionTriggered()) {
                            ++$executedEventCount;
                            ++$rootExecutedCount;

                            $this->logger->debug(
                                'CAMPAIGN: Decision ID# '.$event['id'].' for contact ID# '.$lead->getId()
                                .' noted as completed by event listener thus executing children.'
                            );

                            // Decision has already been triggered by the lead so process the associated events
                            $decisionLogged = false;
                            foreach ($decisionEvent['children'] as $childEvent) {
                                if ($this->executeEvent(
                                        $childEvent,
                                        $campaign,
                                        $lead,
                                        $eventSettings,
                                        false,
                                        null,
                                        null,
                                        false,
                                        $evaluatedEventCount,
                                        $executedEventCount,
                                        $totalEventCount
                                    )
                                    && !$decisionLogged
                                ) {
                                    // Log the decision
                                    $log = $this->getLogEntity($decisionEvent['id'], $campaign, $lead, null, true);
                                    $log->setDateTriggered(new \DateTime());
                                    $log->setNonActionPathTaken(true);
                                    $logRepo->saveEntity($log);
                                    $this->em->detach($log);
                                    unset($log);

                                    $decisionLogged = true;
                                }
                            }
                        }

                        unset($decisionEvent);
                    } else {
                        if ($this->executeEvent(
                            $event,
                            $campaign,
                            $lead,
                            $eventSettings,
                            false,
                            null,
                            null,
                            false,
                            $evaluatedEventCount,
                            $executedEventCount,
                            $totalEventCount
                        )
                        ) {
                            ++$rootExecutedCount;
                        }
                    }

                    unset($event);

                    if ($output && $rootEvaluatedCount < $maxCount) {
                        $progress->setProgress($rootEvaluatedCount);
                    }
                }

                // Free some RAM
                $this->em->detach($lead);
                unset($lead);

                ++$leadDebugCounter;
            }

            $this->em->clear('Mautic\LeadBundle\Entity\Lead');
            $this->em->clear('Mautic\UserBundle\Entity\User');

            unset($leads, $campaignLeads);

            // Free some memory
            gc_collect_cycles();

            $this->triggerConditions($campaign, $evaluatedEventCount, $executedEventCount, $totalEventCount);

            ++$batchDebugCounter;
        }

        if ($output) {
            $progress->finish();
            $output->writeln('');
        }

        $counts = [
            'events'         => $totalStartingEvents,
            'evaluated'      => $rootEvaluatedCount,
            'executed'       => $rootExecutedCount,
            'totalEvaluated' => $evaluatedEventCount,
            'totalExecuted'  => $executedEventCount,
        ];
        $this->logger->debug('CAMPAIGN: Counts - '.var_export($counts, true));

        return ($returnCounts) ? $counts : $executedEventCount;
    }

    /**
     * @param Campaign        $campaign
     * @param                 $totalEventCount
     * @param int             $limit
     * @param bool            $max
     * @param OutputInterface $output
     * @param bool|false      $returnCounts    If true, returns array of counters
     *
     * @return int
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function triggerScheduledEvents(
        $campaign,
        &$totalEventCount,
        $limit = 100,
        $max = false,
        OutputInterface $output = null,
        $returnCounts = false
    ) {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $campaignId   = $campaign->getId();
        $campaignName = $campaign->getName();

        $this->logger->debug('CAMPAIGN: Triggering scheduled events');

        $repo = $this->getRepository();

        // Get a count
        $totalScheduledCount = $repo->getScheduledEvents($campaignId, true);
        $this->logger->debug('CAMPAIGN: '.$totalScheduledCount.' events scheduled to execute.');

        if ($output) {
            $output->writeln(
                $this->translator->trans(
                    'mautic.campaign.trigger.event_count',
                    ['%events%' => $totalScheduledCount, '%batch%' => $limit]
                )
            );
        }

        if (empty($totalScheduledCount)) {
            $this->logger->debug('CAMPAIGN: No events to trigger');

            return ($returnCounts) ? [
                'events'         => 0,
                'evaluated'      => 0,
                'executed'       => 0,
                'totalEvaluated' => 0,
                'totalExecuted'  => 0,
            ] : 0;
        }

        // Get events to avoid joins
        $campaignEvents = $repo->getCampaignActionAndConditionEvents($campaignId);

        // Event settings
        $eventSettings = $this->campaignModel->getEvents();

        $evaluatedEventCount = $executedEventCount = $scheduledEvaluatedCount = $scheduledExecutedCount = 0;
        $maxCount            = ($max) ? $max : $totalScheduledCount;

        // Try to save some memory
        gc_enable();

        if ($output) {
            $progress = ProgressBarHelper::init($output, $maxCount);
            $progress->start();
            if ($max) {
                $progress->setProgress($totalEventCount);
            }
        }

        $sleepBatchCount   = 0;
        $batchDebugCounter = 1;
        while ($scheduledEvaluatedCount < $totalScheduledCount) {
            $this->logger->debug('CAMPAIGN: Batch #'.$batchDebugCounter);

            // Get a count
            $events = $repo->getScheduledEvents($campaignId, false, $limit);

            if (empty($events)) {
                unset($campaignEvents, $event, $leads, $eventSettings);

                $counts = [
                    'events'         => $totalScheduledCount,
                    'evaluated'      => $scheduledEvaluatedCount,
                    'executed'       => $scheduledExecutedCount,
                    'totalEvaluated' => $evaluatedEventCount,
                    'totalExecuted'  => $executedEventCount,
                ];
                $this->logger->debug('CAMPAIGN: Counts - '.var_export($counts, true));

                return ($returnCounts) ? $counts : $executedEventCount;
            }

            $leads = $this->leadModel->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => array_keys($events),
                            ],
                        ],
                    ],
                    'orderBy'            => 'l.id',
                    'orderByDir'         => 'asc',
                    'withPrimaryCompany' => true,
                    'withChannelRules'   => true,
                ]
            );

            if (!count($leads)) {
                // Just a precaution in case non-existent leads are lingering in the campaign leads table
                $this->logger->debug('CAMPAIGN: No contacts entities found');

                break;
            }

            $this->logger->debug('CAMPAIGN: Processing the following contacts '.implode(', ', array_keys($events)));
            $leadDebugCounter = 1;
            foreach ($events as $leadId => $leadEvents) {
                if (!isset($leads[$leadId])) {
                    $this->logger->debug('CAMPAIGN: Lead ID# '.$leadId.' not found');

                    continue;
                }

                /** @var \Mautic\LeadBundle\Entity\Lead $lead */
                $lead = $leads[$leadId];

                $this->logger->debug('CAMPAIGN: Current Lead ID# '.$lead->getId().'; #'.$leadDebugCounter.' in batch #'.$batchDebugCounter);

                // Set lead in case this is triggered by the system
                $this->leadModel->setSystemCurrentLead($lead);

                $this->logger->debug('CAMPAIGN: Processing the following events for contact ID '.$leadId.': '.implode(', ', array_keys($leadEvents)));

                foreach ($leadEvents as $log) {
                    ++$scheduledEvaluatedCount;

                    if ($sleepBatchCount == $limit) {
                        // Keep CPU down
                        $this->batchSleep();
                        $sleepBatchCount = 0;
                    } else {
                        ++$sleepBatchCount;
                    }

                    $event = $campaignEvents[$log['event_id']];

                    // Set campaign ID
                    $event['campaign'] = [
                        'id'   => $campaignId,
                        'name' => $campaignName,
                    ];

                    // Execute event
                    if ($this->executeEvent(
                        $event,
                        $campaign,
                        $lead,
                        $eventSettings,
                        false,
                        null,
                        true,
                        $log['id'],
                        $evaluatedEventCount,
                        $executedEventCount,
                        $totalEventCount
                    )
                    ) {
                        ++$scheduledExecutedCount;
                    }

                    if ($max && $totalEventCount >= $max) {
                        unset($campaignEvents, $event, $leads, $eventSettings);

                        if ($output) {
                            $progress->finish();
                            $output->writeln('');
                        }

                        $this->logger->debug('CAMPAIGN: Max count hit so aborting.');

                        // Hit the max, bye bye

                        $counts = [
                            'events'         => $totalScheduledCount,
                            'evaluated'      => $scheduledEvaluatedCount,
                            'executed'       => $scheduledExecutedCount,
                            'totalEvaluated' => $evaluatedEventCount,
                            'totalExecuted'  => $executedEventCount,
                        ];
                        $this->logger->debug('CAMPAIGN: Counts - '.var_export($counts, true));

                        return ($returnCounts) ? $counts : $executedEventCount;
                    } elseif ($output) {
                        $currentCount = ($max) ? $totalEventCount : $evaluatedEventCount;
                        $progress->setProgress($currentCount);
                    }
                }

                ++$leadDebugCounter;
            }

            // Free RAM
            $this->em->clear('Mautic\LeadBundle\Entity\Lead');
            $this->em->clear('Mautic\UserBundle\Entity\User');
            unset($events, $leads);

            // Free some memory
            gc_collect_cycles();

            ++$batchDebugCounter;

            $this->triggerConditions($campaign, $evaluatedEventCount, $executedEventCount, $totalEventCount);
        }

        if ($output) {
            $progress->finish();
            $output->writeln('');
        }

        $counts = [
            'events'         => $totalScheduledCount,
            'evaluated'      => $scheduledEvaluatedCount,
            'executed'       => $scheduledExecutedCount,
            'totalEvaluated' => $evaluatedEventCount,
            'totalExecuted'  => $executedEventCount,
        ];
        $this->logger->debug('CAMPAIGN: Counts - '.var_export($counts, true));

        return ($returnCounts) ? $counts : $executedEventCount;
    }

    /**
     * Find and trigger the negative events, i.e. the events with a no decision path.
     *
     * @param Campaign        $campaign
     * @param int             $totalEventCount
     * @param int             $limit
     * @param bool            $max
     * @param OutputInterface $output
     * @param bool|false      $returnCounts    If true, returns array of counters
     *
     * @return int
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

        $this->logger->debug('CAMPAIGN: Triggering negative events');

        $campaignId   = $campaign->getId();
        $campaignName = $campaign->getName();

        $repo         = $this->getRepository();
        $campaignRepo = $this->getCampaignRepository();
        $logRepo      = $this->getLeadEventLogRepository();

        // Get events to avoid large number of joins
        $campaignEvents = $repo->getCampaignEvents($campaignId);

        // Get an array of events that are non-action based
        $nonActionEvents = [];
        $actionEvents    = [];
        foreach ($campaignEvents as $id => $e) {
            if (!empty($e['decisionPath']) && !empty($e['parent_id']) && $campaignEvents[$e['parent_id']]['eventType'] != 'condition') {
                if ($e['decisionPath'] == 'no') {
                    $nonActionEvents[$e['parent_id']][$id] = $e;
                } elseif ($e['decisionPath'] == 'yes') {
                    $actionEvents[$e['parent_id']][] = $id;
                }
            }
        }

        $this->logger->debug('CAMPAIGN: Processing the children of the following events: '.implode(', ', array_keys($nonActionEvents)));

        if (empty($nonActionEvents)) {
            // No non-action events associated with this campaign
            unset($campaignEvents);

            return 0;
        }

        // Get a count
        $leadCount = $campaignRepo->getCampaignLeadCount($campaignId);

        if ($output) {
            $output->writeln(
                $this->translator->trans(
                    'mautic.campaign.trigger.lead_count_analyzed',
                    ['%leads%' => $leadCount, '%batch%' => $limit]
                )
            );
        }

        $start               = $leadProcessedCount               = $lastRoundPercentage               = $executedEventCount               = $evaluatedEventCount               = $negativeExecutedCount               = $negativeEvaluatedCount               = 0;
        $nonActionEventCount = $leadCount * count($nonActionEvents);
        $eventSettings       = $this->campaignModel->getEvents();
        $maxCount            = ($max) ? $max : $nonActionEventCount;

        // Try to save some memory
        gc_enable();

        if ($leadCount) {
            if ($output) {
                $progress = ProgressBarHelper::init($output, $maxCount);
                $progress->start();
                if ($max) {
                    $progress->advance($totalEventCount);
                }
            }

            $sleepBatchCount   = 0;
            $batchDebugCounter = 1;
            while ($start <= $leadCount) {
                $this->logger->debug('CAMPAIGN: Batch #'.$batchDebugCounter);

                // Get batched campaign ids
                $campaignLeads = $campaignRepo->getCampaignLeads($campaignId, $start, $limit, ['cl.lead_id, cl.date_added']);

                $campaignLeadIds   = [];
                $campaignLeadDates = [];
                foreach ($campaignLeads as $r) {
                    $campaignLeadIds[]                = $r['lead_id'];
                    $campaignLeadDates[$r['lead_id']] = $r['date_added'];
                }

                unset($campaignLeads);

                $this->logger->debug('CAMPAIGN: Processing the following contacts: '.implode(', ', $campaignLeadIds));

                foreach ($nonActionEvents as $parentId => $events) {
                    // Just a check to ensure this is an appropriate action
                    if ($campaignEvents[$parentId]['eventType'] == 'action') {
                        $this->logger->debug('CAMPAIGN: Parent event ID #'.$parentId.' is an action.');

                        continue;
                    }

                    // Get only leads who have had the action prior to the decision executed
                    $grandParentId = $campaignEvents[$parentId]['parent_id'];

                    // Get the lead log for this batch of leads limiting to those that have already triggered
                    // the decision's parent and haven't executed this level in the path yet
                    if ($grandParentId) {
                        $this->logger->debug('CAMPAIGN: Checking for contacts based on grand parent execution.');

                        $leadLog         = $repo->getEventLog($campaignId, $campaignLeadIds, [$grandParentId], array_keys($events), true);
                        $applicableLeads = array_keys($leadLog);
                    } else {
                        $this->logger->debug('CAMPAIGN: Checking for contacts based on exclusion due to being at root level');

                        // The event has no grandparent (likely because the decision is first in the campaign) so find leads that HAVE
                        // already executed the events in the root level and exclude them
                        $havingEvents = (isset($actionEvents[$parentId]))
                            ? array_merge($actionEvents[$parentId], array_keys($events))
                            : array_keys(
                                $events
                            );
                        $leadLog           = $repo->getEventLog($campaignId, $campaignLeadIds, $havingEvents);
                        $unapplicableLeads = array_keys($leadLog);

                        // Only use leads that are not applicable
                        $applicableLeads = array_diff($campaignLeadIds, $unapplicableLeads);

                        unset($unapplicableLeads);
                    }

                    if (empty($applicableLeads)) {
                        $this->logger->debug('CAMPAIGN: No events are applicable');

                        continue;
                    }

                    $this->logger->debug('CAMPAIGN: These contacts have have not gone down the positive path: '.implode(', ', $applicableLeads));

                    // Get the leads
                    $leads = $this->leadModel->getEntities(
                        [
                            'filter' => [
                                'force' => [
                                    [
                                        'column' => 'l.id',
                                        'expr'   => 'in',
                                        'value'  => $applicableLeads,
                                    ],
                                ],
                            ],
                            'orderBy'            => 'l.id',
                            'orderByDir'         => 'asc',
                            'withPrimaryCompany' => true,
                            'withChannelRules'   => true,
                        ]
                    );

                    if (!count($leads)) {
                        // Just a precaution in case non-existent leads are lingering in the campaign leads table
                        $this->logger->debug('CAMPAIGN: No contact entities found.');

                        continue;
                    }

                    // Loop over the non-actions and determine if it has been processed for this lead

                    $leadDebugCounter = 1;
                    /** @var \Mautic\LeadBundle\Entity\Lead $lead */
                    foreach ($leads as $lead) {
                        ++$negativeEvaluatedCount;

                        // Set lead for listeners
                        $this->leadModel->setSystemCurrentLead($lead);

                        $this->logger->debug('CAMPAIGN: contact ID #'.$lead->getId().'; #'.$leadDebugCounter.' in batch #'.$batchDebugCounter);

                        // Prevent path if lead has already gone down this path
                        if (!isset($leadLog[$lead->getId()]) || !array_key_exists($parentId, $leadLog[$lead->getId()])) {

                            // Get date to compare against
                            $utcDateString = ($grandParentId) ? $leadLog[$lead->getId()][$grandParentId]['date_triggered']
                                : $campaignLeadDates[$lead->getId()];

                            // Convert to local DateTime
                            $grandParentDate = (new DateTimeHelper($utcDateString))->getLocalDateTime();

                            // Non-decision has not taken place yet, so cycle over each associated action to see if timing is right
                            $eventTiming   = [];
                            $executeAction = false;
                            foreach ($events as $id => $e) {
                                if ($sleepBatchCount == $limit) {
                                    // Keep CPU down
                                    $this->batchSleep();
                                    $sleepBatchCount = 0;
                                } else {
                                    ++$sleepBatchCount;
                                }

                                if (isset($leadLog[$lead->getId()]) && array_key_exists($id, $leadLog[$lead->getId()])) {
                                    $this->logger->debug('CAMPAIGN: Event (ID #'.$id.') has already been executed');
                                    unset($e);

                                    continue;
                                }

                                if (!isset($eventSettings[$e['eventType']][$e['type']])) {
                                    $this->logger->debug('CAMPAIGN: Event (ID #'.$id.') no longer exists');
                                    unset($e);

                                    continue;
                                }

                                // First get the timing for all the 'non-decision' actions
                                $eventTiming[$id] = $this->checkEventTiming($e, $grandParentDate, true);
                                if ($eventTiming[$id] === true) {
                                    // Includes events to be executed now then schedule the rest if applicable
                                    $executeAction = true;
                                }

                                unset($e);
                            }

                            if (!$executeAction) {
                                $negativeEvaluatedCount += count($nonActionEvents);

                                // Timing is not appropriate so move on to next lead
                                unset($eventTiming);

                                continue;
                            }

                            if ($max && ($totalEventCount + count($nonActionEvents)) >= $max) {

                                // Hit the max or will hit the max while mid-process for the lead
                                if ($output) {
                                    $progress->finish();
                                    $output->writeln('');
                                }

                                $counts = [
                                    'events'         => $nonActionEventCount,
                                    'evaluated'      => $negativeEvaluatedCount,
                                    'executed'       => $negativeExecutedCount,
                                    'totalEvaluated' => $evaluatedEventCount,
                                    'totalExecuted'  => $executedEventCount,
                                ];
                                $this->logger->debug('CAMPAIGN: Counts - '.var_export($counts, true));

                                return ($returnCounts) ? $counts : $executedEventCount;
                            }

                            $decisionLogged = false;

                            // Execute or schedule events
                            $this->logger->debug(
                                'CAMPAIGN: Processing the following events for contact ID# '.$lead->getId().': '.implode(
                                    ', ',
                                    array_keys($eventTiming)
                                )
                            );

                            foreach ($eventTiming as $id => $eventTriggerDate) {
                                // Set event
                                $event             = $events[$id];
                                $event['campaign'] = [
                                    'id'   => $campaignId,
                                    'name' => $campaignName,
                                ];

                                // Set lead in case this is triggered by the system
                                $this->leadModel->setSystemCurrentLead($lead);

                                if ($this->executeEvent(
                                    $event,
                                    $campaign,
                                    $lead,
                                    $eventSettings,
                                    false,
                                    null,
                                    $eventTriggerDate,
                                    false,
                                    $evaluatedEventCount,
                                    $executedEventCount,
                                    $totalEventCount
                                )
                                ) {
                                    if (!$decisionLogged) {
                                        // Log the decision
                                        $log = $this->getLogEntity($parentId, $campaign, $lead, null, true);
                                        $log->setDateTriggered(new \DateTime());
                                        $log->setNonActionPathTaken(true);
                                        $logRepo->saveEntity($log);
                                        $this->em->detach($log);
                                        unset($log);

                                        $decisionLogged = true;
                                    }

                                    ++$negativeExecutedCount;
                                }

                                unset($utcDateString, $grandParentDate);
                            }
                        } else {
                            $this->logger->debug('CAMPAIGN: Decision has already been executed.');
                        }

                        $currentCount = ($max) ? $totalEventCount : $negativeEvaluatedCount;
                        if ($output && $currentCount < $maxCount) {
                            $progress->setProgress($currentCount);
                        }

                        ++$leadDebugCounter;

                        // Save RAM
                        $this->em->detach($lead);
                        unset($lead);
                    }
                }

                // Next batch
                $start += $limit;

                // Save RAM
                $this->em->clear('Mautic\LeadBundle\Entity\Lead');
                $this->em->clear('Mautic\UserBundle\Entity\User');

                unset($leads, $campaignLeadIds, $leadLog);

                $currentCount = ($max) ? $totalEventCount : $negativeEvaluatedCount;
                if ($output && $currentCount < $maxCount) {
                    $progress->setProgress($currentCount);
                }

                // Free some memory
                gc_collect_cycles();

                ++$batchDebugCounter;
            }

            if ($output) {
                $progress->finish();
                $output->writeln('');
            }

            $this->triggerConditions($campaign, $evaluatedEventCount, $executedEventCount, $totalEventCount);
        }

        $counts = [
            'events'         => $nonActionEventCount,
            'evaluated'      => $negativeEvaluatedCount,
            'executed'       => $negativeExecutedCount,
            'totalEvaluated' => $evaluatedEventCount,
            'totalExecuted'  => $executedEventCount,
        ];
        $this->logger->debug('CAMPAIGN: Counts - '.var_export($counts, true));

        return ($returnCounts) ? $counts : $executedEventCount;
    }

    /**
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
                                'filter' => [
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
     * Execute or schedule an event. Condition events are executed recursively.
     *
     * @param array          $event
     * @param Campaign       $campaign
     * @param Lead           $lead
     * @param array          $eventSettings
     * @param bool           $allowNegative
     * @param \DateTime      $parentTriggeredDate
     * @param \DateTime|bool $eventTriggerDate
     * @param bool           $logExists
     * @param int            $evaluatedEventCount The number of events evaluated for the current method (kickoff, negative/inaction, scheduled)
     * @param int            $executedEventCount  The number of events successfully executed for the current method
     * @param int            $totalEventCount     The total number of events across all methods
     *
     * @return bool
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
        ++$evaluatedEventCount;
        ++$totalEventCount;

        // Get event settings if applicable
        if ($eventSettings === null) {
            $eventSettings = $this->campaignModel->getEvents();
        }

        // Set date timing should be compared with if applicable
        if ($parentTriggeredDate === null) {
            // Default to today
            $parentTriggeredDate = new \DateTime();
        }

        $repo    = $this->getRepository();
        $logRepo = $this->getLeadEventLogRepository();

        if (isset($eventSettings[$event['eventType']][$event['type']])) {
            $thisEventSettings = $eventSettings[$event['eventType']][$event['type']];
        } else {
            $this->logger->debug(
                'CAMPAIGN: Settings not found for '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
            );
            unset($event);

            return false;
        }

        if ($event['eventType'] == 'condition') {
            $allowNegative = true;
        }

        // Set campaign ID
        $event['campaign'] = [
            'id'   => $campaign->getId(),
            'name' => $campaign->getName(),
        ];

        // Ensure properties is an array
        if ($event['properties'] === null) {
            $event['properties'] = [];
        } elseif (!is_array($event['properties'])) {
            $event['properties'] = unserialize($event['properties']);
        }

        // Ensure triggerDate is a \DateTime
        if ($event['triggerMode'] == 'date' && !$event['triggerDate'] instanceof \DateTime) {
            $triggerDate          = new DateTimeHelper($event['triggerDate']);
            $event['triggerDate'] = $triggerDate->getDateTime();
            unset($triggerDate);
        }

        if ($eventTriggerDate == null) {
            $eventTriggerDate = $this->checkEventTiming($event, $parentTriggeredDate, $allowNegative);
        }
        $result = true;

        // Create/get log entry
        if ($logExists) {
            if (true === $logExists) {
                $log = $logRepo->findOneBy(
                    [
                        'lead'  => $lead->getId(),
                        'event' => $event['id'],
                    ]
                );
            } else {
                $log = $this->em->getReference('MauticCampaignBundle:LeadEventLog', $logExists);
            }
        }

        if (empty($log)) {
            $log = $this->getLogEntity($event['id'], $campaign, $lead, null, !defined('MAUTIC_CAMPAIGN_NOT_SYSTEM_TRIGGERED'));
        }

        if ($eventTriggerDate instanceof \DateTime) {
            ++$executedEventCount;

            $log->setTriggerDate($eventTriggerDate);
            $logRepo->saveEntity($log);

            //lead actively triggered this event, a decision wasn't involved, or it was system triggered and a "no" path so schedule the event to be fired at the defined time
            $this->logger->debug(
                'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                .' has timing that is not appropriate and thus scheduled for '.$eventTriggerDate->format('Y-m-d H:m:i T')
            );

            if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_SCHEDULED)) {
                $args = [
                    'eventSettings'   => $thisEventSettings,
                    'eventDetails'    => null,
                    'event'           => $event,
                    'lead'            => $lead,
                    'systemTriggered' => true,
                    'dateScheduled'   => $eventTriggerDate,
                ];

                $scheduledEvent = new CampaignScheduledEvent($args);
                $this->dispatcher->dispatch(CampaignEvents::ON_EVENT_SCHEDULED, $scheduledEvent);
                unset($scheduledEvent, $args);
            }
        } elseif ($eventTriggerDate) {
            // If log already existed, assume it was scheduled in order to not force
            // Doctrine to do a query to fetch the information
            $wasScheduled = (!$logExists) ? $log->getIsScheduled() : true;

            $log->setIsScheduled(false);
            $log->setDateTriggered(new \DateTime());

            try {
                // Save before executing event to ensure it's not picked up again
                $logRepo->saveEntity($log);
                $this->logger->debug(
                    'CAMPAIGN: Created log for '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' prior to execution.'
                );
            } catch (EntityNotFoundException $exception) {
                // The lead has been likely removed from this lead/list
                $this->logger->debug(
                    'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' wasn\'t found: '.$exception->getMessage()
                );

                return false;
            } catch (DBALException $exception) {
                $this->logger->debug(
                    'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' failed with DB error: '.$exception->getMessage()
                );

                return false;
            }

            // Set the channel
            $this->campaignModel->setChannelFromEventProperties($log, $event, $thisEventSettings);

            //trigger the action
            $response = $this->invokeEventCallback($event, $thisEventSettings, $lead, null, true, $log);

            // Check if the lead wasn't deleted during the event callback
            if (null === $lead->getId() && $response === true) {
                ++$executedEventCount;

                $this->logger->debug(
                    'CAMPAIGN: Contact was deleted while executing '.ucfirst($event['eventType']).' ID# '.$event['id']
                );

                return true;
            }

            $eventTriggered = false;
            if ($response instanceof LeadEventLog) {
                // Listener handled the event and returned a log entry
                $this->campaignModel->setChannelFromEventProperties($response, $event, $thisEventSettings);

                $repo->saveEntity($response);
                $this->em->detach($response);

                ++$executedEventCount;

                $this->logger->debug(
                    'CAMPAIGN: Listener handled event for '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                );

                if (!$response->getIsScheduled()) {
                    $eventTriggered = true;
                }
            } elseif (($response === false || (is_array($response) && isset($response['result']) && false === $response['result']))
                && $event['eventType'] == 'action'
            ) {
                $result = false;
                $debug  = 'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' failed with a response of '.var_export($response, true);

                // Something failed
                if ($wasScheduled || !empty($this->scheduleTimeForFailedEvents)) {
                    $date = new \DateTime();
                    $date->add(new \DateInterval($this->scheduleTimeForFailedEvents));

                    // Reschedule
                    $log->setTriggerDate($date);

                    if (is_array($response)) {
                        $log->setMetadata($response);
                    }
                    $logRepo->saveEntity($log);
                    $debug .= ' thus placed on hold '.$this->scheduleTimeForFailedEvents;
                } else {
                    // Remove
                    $debug .= ' thus deleted';
                    $repo->deleteEntity($log);
                }

                // Notify the lead owner if there is one otherwise campaign creator that there was a failure
                if (!$owner = $lead->getOwner()) {
                    $ownerId = $campaign->getCreatedBy();
                    $owner   = $this->userModel->getEntity($ownerId);
                }

                if ($owner && $owner->getId()) {
                    $this->notificationModel->addNotification(
                        $campaign->getName().' / '.$event['name'],
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

                $this->logger->debug($debug);
            } else {
                ++$executedEventCount;

                if ($response !== true) {
                    if ($this->triggeredResponses !== false) {
                        $eventTypeKey = $event['eventType'];
                        $typeKey      = $event['type'];

                        if (!array_key_exists($eventTypeKey, $this->triggeredResponses) || !is_array($this->triggeredResponses[$eventTypeKey])) {
                            $this->triggeredResponses[$eventTypeKey] = [];
                        }

                        if (!array_key_exists($typeKey, $this->triggeredResponses[$eventTypeKey])
                            || !is_array(
                                $this->triggeredResponses[$eventTypeKey][$typeKey]
                            )
                        ) {
                            $this->triggeredResponses[$eventTypeKey][$typeKey] = [];
                        }

                        $this->triggeredResponses[$eventTypeKey][$typeKey][$event['id']] = $response;
                    }

                    $log->setMetadata($response);
                }

                $logRepo->saveEntity($log);

                $this->logger->debug(
                    'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' successfully executed and logged with a response of '.var_export($response, true)
                );

                $eventTriggered = true;
            }

            if ($eventTriggered) {
                // Collect the events that were triggered so that conditions can be handled properly

                if ('condition' === $event['eventType']) {
                    // Conditions will need child event processed
                    $decisionPath = ($response === true) ? 'yes' : 'no';
                } else {
                    // Actions will need to check if conditions are attached to it
                    $decisionPath = 'null';
                }

                if (!isset($this->triggeredEvents[$event['id']])) {
                    $this->triggeredEvents[$event['id']] = [];
                }
                if (!isset($this->triggeredEvents[$event['id']][$decisionPath])) {
                    $this->triggeredEvents[$event['id']][$decisionPath] = [];
                }

                $this->triggeredEvents[$event['id']][$decisionPath][] = $lead->getId();
            }
        } else {
            //else do nothing
            $result = false;
            $this->logger->debug(
                'CAMPAIGN: Timing failed ('.gettype($eventTriggerDate).') for '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '
                .$lead->getId()
            );
        }

        if (!empty($log)) {
            // Detach log
            $this->em->detach($log);
            unset($log);
        }

        unset($eventTriggerDate, $event);

        return $result;
    }

    /**
     * Invoke the event's callback function.
     *
     * @param              $event
     * @param              $settings
     * @param null         $lead
     * @param null         $eventDetails
     * @param bool         $systemTriggered
     * @param LeadEventLog $log
     *
     * @return bool|mixed
     */
    public function invokeEventCallback($event, $settings, $lead = null, $eventDetails = null, $systemTriggered = false, LeadEventLog $log = null)
    {
        if (isset($settings['eventName'])) {
            // Create a campaign event with a default successful result
            $campaignEvent = new CampaignExecutionEvent(
                [
                    'eventSettings'   => $settings,
                    'eventDetails'    => $eventDetails,
                    'event'           => $event,
                    'lead'            => $lead,
                    'systemTriggered' => $systemTriggered,
                    'config'          => $event['properties'],
                ],
                true,
                $log
            );

            $eventName = array_key_exists('eventName', $settings) ? $settings['eventName'] : null;

            if ($eventName && $this->dispatcher->hasListeners($eventName)) {
                $this->dispatcher->dispatch($eventName, $campaignEvent);

                if ($event['eventType'] !== 'decision' && $this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_EXECUTION)) {
                    $this->dispatcher->dispatch(CampaignEvents::ON_EVENT_EXECUTION, $campaignEvent);
                }

                if ($campaignEvent->wasLogUpdatedByListener()) {
                    $campaignEvent->setResult($campaignEvent->getLogEntry());
                }
            }

            if (null !== $log) {
                $log->setChannel($campaignEvent->getChannel())
                    ->setChannelId($campaignEvent->getChannelId());
            }

            return $campaignEvent->getResult();
        }

        /*
         * @deprecated 2.0 - to be removed in 3.0; Use the new eventName method instead
         */
        if (isset($settings['callback']) && is_callable($settings['callback'])) {
            $args = [
                'eventSettings'   => $settings,
                'eventDetails'    => $eventDetails,
                'event'           => $event,
                'lead'            => $lead,
                'factory'         => $this->factory,
                'systemTriggered' => $systemTriggered,
                'config'          => $event['properties'],
            ];

            if (is_array($settings['callback'])) {
                $reflection = new \ReflectionMethod($settings['callback'][0], $settings['callback'][1]);
            } elseif (strpos($settings['callback'], '::') !== false) {
                $parts      = explode('::', $settings['callback']);
                $reflection = new \ReflectionMethod($parts[0], $parts[1]);
            } else {
                $reflection = new \ReflectionMethod(null, $settings['callback']);
            }

            $pass = [];
            foreach ($reflection->getParameters() as $param) {
                if (isset($args[$param->getName()])) {
                    $pass[] = $args[$param->getName()];
                } else {
                    $pass[] = null;
                }
            }

            $result = $reflection->invokeArgs($this, $pass);

            if ('decision' != $event['eventType'] && $this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_EXECUTION)) {
                $executionEvent = $this->dispatcher->dispatch(
                    CampaignEvents::ON_EVENT_EXECUTION,
                    new CampaignExecutionEvent($args, $result, $log)
                );

                if ($executionEvent->wasLogUpdatedByListener()) {
                    $result = $executionEvent->getLogEntry();
                }
            }
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Check to see if the interval between events are appropriate to fire currentEvent.
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
                $unit     = strtoupper($action['triggerIntervalUnit']);

                $this->logger->debug('CAMPAIGN: Adding interval of '.$interval.$unit.' to '.$triggerOn->format('Y-m-d H:i:s T'));

                switch ($unit) {
                    case 'Y':
                    case 'M':
                    case 'D':
                        $dt = "P{$interval}{$unit}";
                        break;
                    case 'I':
                        $dt = "PT{$interval}M";
                        break;
                    case 'H':
                    case 'S':
                        $dt = "PT{$interval}{$unit}";
                        break;
                }

                $dv = new \DateInterval($dt);
                $triggerOn->add($dv);

                if ($triggerOn > $now) {
                    $this->logger->debug(
                        'CAMPAIGN: Date to execute ('.$triggerOn->format('Y-m-d H:i:s T').') is later than now ('.$now->format('Y-m-d H:i:s T')
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
     * Get line chart data of campaign events.
     *
     * @param char      $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getEventLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('campaign_lead_event_log', 'date_triggered', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = c.campaign_id')
              ->andWhere('c.created_by = :userId')
              ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.campaign.triggered.events'), $data);

        return $chart->render();
    }

    /**
     * Handles condition type events.
     *
     * @deprecated 2.6.0 to be removed in 3.0; use triggerConditions() instead
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
     * Batch sleep according to settings.
     */
    protected function batchSleep()
    {
        $eventSleepTime = $this->batchCampaignSleepTime ? $this->batchCampaignSleepTime : ($this->batchSleepTime ? $this->batchSleepTime : 1);

        if (empty($eventSleepTime)) {
            return;
        }

        if ($eventSleepTime < 1) {
            usleep($eventSleepTime * 1000000);
        } else {
            sleep($eventSleepTime);
        }
    }
}
