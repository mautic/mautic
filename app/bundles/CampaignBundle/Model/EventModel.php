<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Doctrine\ORM\EntityNotFoundException;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignDecisionEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\CampaignScheduledEvent;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EventModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class EventModel extends CommonFormModel
{
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
     * Get CampaignRepository
     *
     * @return \Mautic\CampaignBundle\Entity\CampaignRepository
     */
    public function getCampaignRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Campaign');
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
     * Get a specific entity or generate a new one if id is empty
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
     * Delete events
     *
     * @param $currentEvents
     * @param $originalEvents
     * @param $deletedEvents
     */
    public function deleteEvents($currentEvents, $originalEvents, $deletedEvents)
    {
        $orderedDelete = array();
        foreach ($deletedEvents as $k => $deleteMe) {
            if ($deleteMe instanceof Event) {
                $deleteMe = $deleteMe->getId();
            }

            if (strpos($deleteMe, 'new') === 0) {
                continue;
            }

            if (isset($originalEvents[$deleteMe]) && !in_array($deleteMe, $orderedDelete)) {
                $this->buildEventHierarchy($originalEvents[$deleteMe], $orderedDelete);
            }
        }

        //remove any events that are now part of the current events (i.e. a child moved from a deleted parent)
        foreach ($orderedDelete as $k => $deleteMe) {
            if (isset($currentEvents[$deleteMe])) {
                unset($orderedDelete[$k]);
            }
        }

        $this->deleteEntities($orderedDelete);
    }

    /**
     * Build a hierarchy of children and parent entities for deletion
     *
     * @param $entity
     * @param $hierarchy
     */
    public function buildEventHierarchy($entity, &$hierarchy)
    {
        if ($entity instanceof Event) {
            $children = $entity->getChildren();
            $id       = $entity->getId();
        } else {
            $children = (isset($entity['children'])) ? $entity['children'] : array();
            $id       = $entity['id'];
        }
        $hasChildren = count($children) ? true : false;

        if (!$hasChildren) {
            $hierarchy[] = $id;
        } else {
            foreach ($children as $child) {
                $this->buildEventHierarchy($child, $hierarchy);
            }
            $hierarchy[] = $id;
        }
    }

    /**
     * Triggers an event
     *
     * @param       $type
     * @param mixed $eventDetails
     * @param mixed $typeId
     *
     * @return bool|mixed
     */
    public function triggerEvent($type, $eventDetails = null, $typeId = null)
    {
        static $leadCampaigns = array(), $eventList = array(), $availableEventSettings = array(), $leadsEvents = array(), $examinedEvents = array();

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Campaign triggered for event type '.$type.'('.$typeId.')');

        // Skip the anonymous check to force actions to fire for subsequant triggers
        $systemTriggered = defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED');

        //only trigger events for anonymous users (to prevent populating full of user/company data)
        if (!$systemTriggered && !$this->security->isAnonymous()) {
            $logger->debug('CAMPAIGN: lead not anonymous; abort');

            return false;
        }

        //get the current lead
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');
        $lead      = $leadModel->getCurrentLead();
        $leadId    = $lead->getId();
        $logger->debug('CAMPAIGN: Current Lead ID: '.$leadId);

        //get the lead's campaigns so we have when the lead was added
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');
        if (empty($leadCampaigns[$leadId])) {
            $leadCampaigns[$leadId] = $campaignModel->getLeadCampaigns($lead, true);
        }

        if (empty($leadCampaigns[$leadId])) {
            $logger->debug('CAMPAIGN: no campaigns found so abort');

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
            $availableEventSettings = $campaignModel->getEvents();
        }

        //make sure there are events before continuing
        if (!count($availableEventSettings) || empty($events)) {
            $logger->debug('CAMPAIGN: no events found so abort');

            return false;
        }

        //get a list of events that has already been executed for this lead
        if (empty($leadsEvents[$leadId])) {
            $leadsEvents[$leadId] = $eventRepo->getLeadTriggeredEvents($leadId);
        }

        //IP address for the log
        /** @var \Mautic\CoreBundle\Entity\IpAddress $ipAddress */
        $ipAddress = $this->factory->getIpAddress();

        //Store all the entities to be persisted so that it can be done at one time
        $persist = array();

        if (!isset($examinedEvents[$leadId])) {
            $examinedEvents[$leadId] = array();
        }

        $actionResponses = array();
        foreach ($events as $campaignId => $campaignEvents) {
            foreach ($campaignEvents as $k => $event) {
                //has this event already been examined via a parent's children?
                //all events of this triggering type has to be queried since this particular event could be anywhere in the dripflow
                if (in_array($event['id'], $examinedEvents[$leadId])) {
                    $logger->debug('CAMPAIGN: ID# '.$event['id'].' already processed this round');
                    continue;
                }
                $examinedEvents[$leadId][] = $event['id'];

                //check to see if this has been fired sequentially
                if (!empty($event['parent'])) {
                    if (!isset($leadsEvents[$leadId][$event['parent']['id']])) {
                        //this event has a parent that has not been triggered for this lead so break out
                        $logger->debug(
                            'CAMPAIGN: parent (ID# '.$event['parent']['id'].') for ID# '.$event['id']
                            .' has not been triggered yet or was triggered with this batch'
                        );
                        continue;
                    }
                    $parentLog = $leadsEvents[$leadId][$event['parent']['id']]['log'][0];

                    if ($parentLog['isScheduled']) {
                        //this event has a parent that is scheduled and thus not triggered
                        $logger->debug(
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
                    $logger->debug('CAMPAIGN: '.$type.' does not exist. (#'.$event['id'].')');

                    continue;
                }

                //check the callback function for the event to make sure it even applies based on its settings
                if (!$this->invokeEventCallback($event, $decisionEventSettings, $lead, $eventDetails, $systemTriggered)) {
                    $logger->debug('CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' callback check failed');

                    continue;
                }

                if (!empty($event['children'])) {
                    $logger->debug('CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' has children');

                    $childrenTriggered = false;
                    foreach ($event['children'] as $child) {
                        if (isset($leadsEvents[$leadId][$child['id']])) {
                            //this child event has already been fired for this lead so move on to the next event
                            $logger->debug('CAMPAIGN: '.ucfirst($child['eventType']).' ID# '.$child['id'].' already triggered');
                            continue;
                        } elseif ($child['eventType'] == 'decision') {
                            //hit a triggering type event so move on
                            $logger->debug('CAMPAIGN: ID# '.$child['id'].' is a decision');

                            continue;
                        } else {
                            $logger->debug('CAMPAIGN: '.ucfirst($child['eventType']).' ID# '.$child['id'].' is being processed');
                        }

                        if (isset($availableEventSettings[$child['eventType']][$child['type']])) {
                            $thisEventSettings = $availableEventSettings[$child['eventType']][$child['type']];
                        } else {
                            // Not found maybe it's no longer available?
                            $logger->debug('CAMPAIGN: '.ucfirst($child['eventType']).' '.$child['type'].' does not exist. (#'.$child['id'].')');

                            continue;
                        }

                        //store in case a child was pulled with events
                        $examinedEvents[$leadId][] = $child['id'];

                        $timing = $this->checkEventTiming($child, $parentTriggeredDate);
                        if ($timing instanceof \DateTime) {
                            //lead actively triggered this event, a decision wasn't involved, or it was system triggered and a "no" path so schedule the event to be fired at the defined time
                            $logger->debug(
                                'CAMPAIGN: '.ucfirst($child['eventType']).' ID# '.$child['id'].' timing is not appropriate and thus scheduled for '
                                .$timing->format('Y-m-d H:m:i T')
                            );

                            $log = $this->getLogEntity($child['id'], $event['campaign']['id'], $lead, $ipAddress, $systemTriggered);
                            $log->setIsScheduled(true);
                            $log->setTriggerDate($timing);
                            $persist[] = $log;

                            $childrenTriggered = true;

                            if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_SCHEDULED)) {
                                $args = array(
                                    'eventSettings'   => $thisEventSettings,
                                    'eventDetails'    => $eventDetails,
                                    'event'           => $event,
                                    'lead'            => $lead,
                                    'factory'         => $this->factory,
                                    'systemTriggered' => $systemTriggered,
                                    'dateScheduled'   => $timing
                                );

                                $scheduledEvent = new CampaignScheduledEvent($args);
                                $this->dispatcher->dispatch(CampaignEvents::ON_EVENT_SCHEDULED, $scheduledEvent);
                                unset($scheduledEvent, $args);
                            }

                            continue;
                        } elseif (!$timing) {
                            //timing not appropriate and should not be scheduled so bail
                            $logger->debug(
                                'CAMPAIGN: '.ucfirst($child['eventType']).' ID# '.$child['id'].' timing is not appropriate and not scheduled.'
                            );
                            continue;
                        }

                        //trigger the action
                        $response = $this->invokeEventCallback($child, $thisEventSettings, $lead, $eventDetails, $systemTriggered);
                        if ($response !== false || $child['eventType'] == 'condition') {
                            $logger->debug('CAMPAIGN: '.ucfirst($child['eventType']).' ID# '.$child['id'].' successfully executed and logged.');
                            $log = $this->getLogEntity($child['id'], $child['campaign']['id'], $lead, $ipAddress, $systemTriggered);

                            $childrenTriggered = true;

                            if ($response !== true) {
                                // Some feed back was given to be passed back to the function calling triggerEvent
                                $actionResponses[$child['eventType']][$child['id']] = $response;
                                $log->setMetadata($response);
                            }

                            $persist[] = $log;
                            unset($log);
                        } elseif ($child['eventType'] != 'condition') {
                            $logger->debug('CAMPAIGN: '.ucfirst($child['eventType']).' ID# '.$child['id'].' execution failed.');
                        }

                        if ($this->handleCondition($response, $availableEventSettings, $child, $campaignModel->getEntity($campaignId), $lead)) {
                            $childrenTriggered = true;
                        }
                    }

                    if ($childrenTriggered) {
                        $logger->debug('CAMPAIGN: Decision ID# '.$event['id'].' successfully executed and logged.');

                        //a child of this event was triggered or scheduled so make not of the triggering event in the log
                        $persist[] = $this->getLogEntity($event['id'], $event['campaign']['id'], $lead, $ipAddress, $systemTriggered);
                    } else {
                        $logger->debug('CAMPAIGN: Decision not logged');
                    }
                } else {
                    $logger->debug('CAMPAIGN: No children for this event.');
                }
            }
        }

        if ($lead->getChanges()) {
            $leadModel->saveEntity($lead, false);
        }

        if (!empty($persist)) {
            $this->getRepository()->saveEntities($persist);
        }

        if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_DECISION_TRIGGER)) {
            $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_DECISION_TRIGGER,
                new CampaignDecisionEvent($lead, $type, $eventDetails, $events, $availableEventSettings, $persist)
            );
        }

        return $actionResponses;
    }

    /**
     * Trigger the root level action(s) in campaign(s)
     *
     * @param Campaign        $campaign
     * @param                 $totalEventCount
     * @param int             $limit
     * @param bool            $max
     * @param OutputInterface $output
     * @param int|null        $leadId
     * @param bool|false      $returnTotalCounts
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
        $returnTotalCounts = false
    ) {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $campaignId = $campaign->getId();

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Triggering starting events');

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        $repo         = $this->getRepository();
        $campaignRepo = $this->getCampaignRepository();

        if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_DECISION_TRIGGER)) {
            // Include decisions if there are listeners
            $events = $repo->getRootLevelEvents($campaignId, true);

            // Filter out decisions
            $decisionChildren = array();
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
            $logger->debug('CAMPAIGN: No events to trigger');

            return 0;
        }

        // Event settings
        $eventSettings = $campaignModel->getEvents();

        // Get a list of leads who have already had the events executed
        // (going to assume if one event of this level has fired for the event, all were fired)
        $ignoreLeads = $repo->getEventLogLeads(array_keys($events), $leadId);

        if ($leadId && in_array($leadId, $ignoreLeads)) {
            $logger->debug('CAMPAIGN: Lead ID '.$leadId.' has already started the campaign');

            return 0;
        }

        // Get a lead count; if $leadId, then use this as a check to ensure lead is part of the campaign
        $leadCount = $campaignRepo->getCampaignLeadCount($campaignId, $ignoreLeads, $leadId);

        // Get a total number of events that will be processed
        $totalEvents = $leadCount * $rootEventCount;

        if ($output) {
            $output->writeln(
                $this->translator->trans(
                    'mautic.campaign.trigger.event_count',
                    array('%events%' => $totalEvents, '%batch%' => $limit)
                )
            );
        }

        if (empty($leadCount)) {
            $logger->debug('CAMPAIGN: No leads to process');

            unset($events);

            return 0;
        }

        $start = $eventCount = $processedCount = 0;

        // Try to save some memory
        gc_enable();

        $maxCount = ($max) ? $max : $totalEvents;

        if ($output) {
            $progress = new ProgressBar($output, $maxCount);
            $progress->start();
        }

        $continue = true;

        $sleepBatchCount = 0;
        while ($continue && $eventCount < $maxCount) {
            // Get list of all campaign leads
            $campaignLeads = ($leadId) ? array($leadId) : $campaignRepo->getCampaignLeadIds($campaignId, $start, $limit, $ignoreLeads);

            if (empty($campaignLeads)) {
                // No leads found

                break;
            }

            $leads = $leadModel->getEntities(
                array(
                    'filter'     => array(
                        'force' => array(
                            array(
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $campaignLeads
                            )
                        )
                    ),
                    'orderBy'    => 'l.id',
                    'orderByDir' => 'asc'
                )
            );

            if (!count($leads)) {
                // Just a precaution in case non-existent leads are lingering in the campaign leads table

                break;
            }

            /** @var \Mautic\LeadBundle\Entity\Lead $lead */
            foreach ($leads as $lead) {
                $logger->debug('CAMPAIGN: Current Lead ID: '.$lead->getId());

                if ($eventCount >= $maxCount || ($max && ($eventCount + $rootEventCount) >= $max)) {
                    // Hit the max or will hit the max mid-progress for a lead
                    $continue = false;

                    break;
                }

                // Set lead in case this is triggered by the system
                $leadModel->setSystemCurrentLead($lead);

                foreach ($events as $event) {

                    if ($sleepBatchCount == $limit) {
                        // Keep CPU down
                        $this->batchSleep();
                        $sleepBatchCount = 0;
                    } else {
                        $sleepBatchCount++;
                    }

                    $eventCount++;

                    if ($event['eventType'] == 'decision') {
                        $event['campaign'] = array(
                            'id'   => $campaign->getId(),
                            'name' => $campaign->getName(),
                        );

                        $decisionEvent = array(
                            $campaignId => array(
                                array_merge(
                                    $event,
                                    array('children' => $decisionChildren[$event['id']])
                                )
                            )
                        );
                        $decisionTriggerEvent = new CampaignDecisionEvent($lead, $event['type'], null, $decisionEvent, $eventSettings, null, true);
                        $this->dispatcher->dispatch(
                            CampaignEvents::ON_EVENT_DECISION_TRIGGER,
                            $decisionTriggerEvent
                        );
                        if ($decisionTriggerEvent->wasDecisionTriggered()) {
                            // Decision has already been triggered by the lead so process the associated events
                            $decisionLogged = false;
                            foreach ($decisionEvent['children'] as $childEvent) {
                                if ($this->executeEvent($childEvent, $campaign, $lead, $eventSettings, false, null, null, $processedCount, $totalEventCount) && !$decisionLogged) {
                                    // Log the decision
                                    $log = $this->getLogEntity($decisionEvent['id'], $campaign, $lead, null, true);
                                    $log->setDateTriggered(new \DateTime());
                                    $log->setNonActionPathTaken(true);
                                    $repo->saveEntity($log);
                                    $this->em->detach($log);
                                    unset($log);

                                    $decisionLogged = true;
                                }
                            }
                        }

                        unset($decisionEvent);
                    } else {
                        $this->executeEvent($event, $campaign, $lead, $eventSettings, false, null, null, $processedCount, $totalEventCount);
                    }

                    unset($event);
                }

                // Free some RAM
                $this->em->detach($lead);
                unset($lead);

                if ($output && $eventCount < $maxCount) {
                    $progress->setProgress($eventCount);
                }
            }

            $start += $limit;

            $this->em->clear('MauticLeadBundle:Lead');
            $this->em->clear('MauticUserBundle:User');

            unset($leads, $campaignLeads);

            // Free some memory
            gc_collect_cycles();
        }

        if ($output) {
            $progress->finish();
            $output->writeln('');
        }

        return ($returnTotalCounts) ? array($totalEventCount, $totalEvents) : $processedCount;
    }

    /**
     * Execute or schedule an event. Condition events are executed recursively
     *
     * @param  array          $event
     * @param  Campaign       $campaign
     * @param  Lead           $lead
     * @param  array          $eventSettings
     * @param  bool           $allowNegative
     * @param  \DateTime      $parentTriggeredDate
     * @param  \DateTime|bool $eventTriggerDate
     * @param  integer        $processedCount
     * @param  integer        $totalEventCount
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
        &$processedCount = 0,
        &$totalEventCount = 0
    ) {
        // Get event settings if applicable
        if ($eventSettings === null) {
            /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
            $campaignModel = $this->factory->getModel('campaign');
            $eventSettings = $campaignModel->getEvents();
        }

        // Set date timing should be compared with if applicable
        if ($parentTriggeredDate === null) {
            // Default to today
            $parentTriggeredDate = new \DateTime();
        }

        $repo   = $this->getRepository();
        $logger = $this->factory->getLogger();

        if (isset($eventSettings[$event['eventType']][$event['type']])) {
            $thisEventSettings = $eventSettings[$event['eventType']][$event['type']];
        } else {
            unset($event);

            return false;
        }

        if ($event['eventType'] == 'condition') {
            $allowNegative = true;
        }

        // Set campaign ID
        $event['campaign'] = array(
            'id'   => $campaign->getId(),
            'name' => $campaign->getName(),
        );

        // Ensure properties is an array
        if ($event['properties'] === null) {
            $event['properties'] = array();
        } elseif (!is_array($event['properties'])) {
            $event['properties'] = unserialize($event['properties']);
        }

        // Ensure triggerDate is a \DateTime
        if ($event['triggerMode'] == 'date' && !$event['triggerDate'] instanceof \DateTime) {
            $triggerDate          = new DateTimeHelper($event['triggerDate']);
            $event['triggerDate'] = $triggerDate->getDateTime();
            unset($triggerDate);
        }

        $logger->debug('CAMPAIGN: Event ID# '.$event['id']);

        if ($eventTriggerDate == null) {
            $eventTriggerDate = $this->checkEventTiming($event, $parentTriggeredDate, $allowNegative);
        }
        $result = true;

        if ($eventTriggerDate instanceof \DateTime) {
            $processedCount++;

            //lead actively triggered this event, a decision wasn't involved, or it was system triggered and a "no" path so schedule the event to be fired at the defined time
            $logger->debug(
                'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' timing is not appropriate and thus scheduled for '
                .$eventTriggerDate->format('Y-m-d H:m:i T').''
            );

            $log = $this->getLogEntity($event['id'], $campaign, $lead, null, true);
            $log->setLead($lead);
            $log->setIsScheduled(true);
            $log->setTriggerDate($eventTriggerDate);

            $repo->saveEntity($log);

            if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_SCHEDULED)) {
                $args = array(
                    'eventSettings'   => $thisEventSettings,
                    'eventDetails'    => null,
                    'event'           => $event,
                    'lead'            => $lead,
                    'factory'         => $this->factory,
                    'systemTriggered' => true,
                    'dateScheduled'   => $eventTriggerDate
                );

                $scheduledEvent = new CampaignScheduledEvent($args);
                $this->dispatcher->dispatch(CampaignEvents::ON_EVENT_SCHEDULED, $scheduledEvent);
                unset($scheduledEvent, $args);
            }
        } elseif ($eventTriggerDate) {
            // Save log first to prevent subsequent triggers from duplicating
            $log = $this->getLogEntity($event['id'], $campaign, $lead, null, true);
            $log->setDateTriggered(new \DateTime());
            $repo->saveEntity($log);

            //trigger the action
            $response = $this->invokeEventCallback($event, $thisEventSettings, $lead, null, true);

            if ($response === false && $event['eventType'] == 'action') {
                $result = false;

                // Something failed so remove the log
                $repo->deleteEntity($log);

                $logger->debug('CAMPAIGN: ID# '.$event['id'].' execution failed.');
            } else {
                $processedCount++;

                if ($response !== true) {
                    $log->setMetadata($response);
                    $repo->saveEntity($log);
                }

                $logger->debug('CAMPAIGN: ID# '.$event['id'].' successfully executed and logged.');
            }

            $this->handleCondition($response, $eventSettings, $event, $campaign, $lead, $processedCount, $totalEventCount);
        } else {
            //else do nothing
            $result = false;
            $logger->debug('CAMPAIGN: Timing failed ('.gettype($eventTriggerDate).')');
        }

        $totalEventCount++;

        if (!empty($log)) {
            // Detach log
            $this->em->detach($log);
            unset($log);
        }

        unset($eventTriggerDate, $event);

        return $result;
    }

    /**
     * Handles condition type events
     *
     * @param  boolean  $response
     * @param  array    $eventSettings
     * @param  array    $event
     * @param  Campaign $campaign
     * @param  Lead     $lead
     * @param  integer  $processedCount
     * @param  integer  $totalEventCount
     *
     * @return bool     True if an event was executed
     */
    public function handleCondition($response, $eventSettings, $event, $campaign, $lead, &$processedCount = 0, &$totalEventCount = 0)
    {
        if (empty($event['eventType']) || $event['eventType'] != 'condition') {

            return false;
        }

        $logger       = $this->factory->getLogger();
        $repo         = $this->getRepository();
        $decisionPath = ($response === true) ? 'yes' : 'no';
        $childEvents  = $repo->getEventsByParent($event['id'], $decisionPath);

        $logger->debug(
            'CAMPAIGN: Condition ID# '.$event['id'].' triggered with '.$decisionPath.' decision path. Has '.count($childEvents).' child event(s).'
        );

        $childExecuted = false;
        foreach ($childEvents as $childEvent) {
            // Trigger child event recursively
            if ($this->executeEvent($childEvent, $campaign, $lead, $eventSettings, true, null, null, $processedCount, $totalEventCount)) {
                $childExecuted = true;
            }
        }

        // Log condition


        return $childExecuted;
    }

    /**
     * @param Campaign        $campaign
     * @param                 $totalEventCount
     * @param int             $limit
     * @param bool            $max
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Doctrine\ORM\ORMException
     */
    public function triggerScheduledEvents($campaign, &$totalEventCount, $limit = 100, $max = false, OutputInterface $output = null)
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $campaignId   = $campaign->getId();
        $campaignName = $campaign->getName();

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Triggering scheduled events');

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        $repo = $this->getRepository();

        // Get a count
        $totalScheduledCount = $repo->getScheduledEvents($campaignId, true);

        if ($output) {
            $output->writeln(
                $this->translator->trans(
                    'mautic.campaign.trigger.event_count',
                    array('%events%' => $totalScheduledCount, '%batch%' => $limit)
                )
            );
        }

        if (empty($totalScheduledCount)) {
            $logger->debug('CAMPAIGN: No events to trigger');

            return 0;
        }

        // Get events to avoid joins
        $campaignEvents = $repo->getCampaignActionAndConditionEvents($campaignId);

        // Event settings
        $eventSettings = $campaignModel->getEvents();

        $eventCount = $processedEvents = 0;
        $maxCount   = ($max) ? $max : $totalScheduledCount;

        // Try to save some memory
        gc_enable();

        if ($output) {
            $progress = new ProgressBar($output, $maxCount);
            $progress->start();
            if ($max) {
                $progress->setProgress($totalEventCount);
            }
        }

        $sleepBatchCount = 0;
        while ($eventCount < $totalScheduledCount) {
            // Get a count
            $events = $repo->getScheduledEvents($campaignId, false, $limit);

            if (empty($events)) {
                unset($campaignEvents, $event, $leads, $eventSettings);

                return $eventCount;
            }

            $leads = $leadModel->getEntities(
                array(
                    'filter'     => array(
                        'force' => array(
                            array(
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => array_keys($events)
                            )
                        )
                    ),
                    'orderBy'    => 'l.id',
                    'orderByDir' => 'asc'
                )
            );

            if (!count($leads)) {
                // Just a precaution in case non-existent leads are lingering in the campaign leads table

                break;
            }

            foreach ($events as $leadId => $leadEvents) {
                if (!isset($leads[$leadId])) {
                    continue;
                }

                /** @var \Mautic\LeadBundle\Entity\Lead $lead */
                $lead = $leads[$leadId];

                $logger->debug('CAMPAIGN: Current Lead ID: '.$lead->getId());

                // Set lead in case this is triggered by the system
                $leadModel->setSystemCurrentLead($lead);

                $persist = array();

                foreach ($leadEvents as $log) {
                    if ($sleepBatchCount == $limit) {
                        // Keep CPU down
                        $this->batchSleep();
                        $sleepBatchCount = 0;
                    } else {
                        $sleepBatchCount++;
                    }

                    $event = $campaignEvents[$log['event_id']];

                    // Set campaign ID
                    $event['campaign'] = array(
                        'id'   => $campaignId,
                        'name' => $campaignName
                    );

                    if (isset($eventSettings['action'][$event['type']])) {
                        $thisEventSettings = $eventSettings['action'][$event['type']];
                    } elseif (isset($eventSettings['condition'][$event['type']])) {
                        $thisEventSettings = $eventSettings['condition'][$event['type']];
                    } else {
                        unset($event);
                        $eventCount++;
                        $totalEventCount++;

                        continue;
                    }

                    //trigger the action
                    $response = $this->invokeEventCallback($event, $thisEventSettings, $lead, null, true);
                    if ($response !== false || $event['eventType'] == 'condition') {
                        $processedEvents++;

                        $logger->debug('CAMPAIGN: ID# '.$event['id'].' successfully executed and logged.');

                        try {
                            $log = $this->em->getReference('MauticCampaignBundle:LeadEventLog', array('lead' => $leadId, 'event' => $event['id']));
                            $log->setTriggerDate(null);
                            $log->setIsScheduled(false);
                            $log->setDateTriggered(new \DateTime());

                            if ($response !== true) {
                                $log->setMetadata($response);
                            }

                            $persist[] = $log;
                            unset($log);

                            $this->handleCondition($response, $eventSettings, $event, $campaign, $lead);
                        } catch (EntityNotFoundException $exception) {
                            // The lead has been likely removed from this lead/list
                        }
                    } else {
                        $logger->debug('CAMPAIGN: ID# '.$event['id'].' execution failed.');
                    }

                    $eventCount++;
                    $totalEventCount++;

                    if ($max && $totalEventCount >= $max) {
                        // Persist then detach
                        if (!empty($persist)) {
                            $repo->saveEntities($persist);
                        }

                        unset($campaignEvents, $event, $leads, $eventSettings);

                        if ($output) {
                            $progress->finish();
                            $output->writeln('');
                        }

                        // Hit the max, bye bye
                        return $eventCount;
                    }
                }

                // Persist then detach
                if (!empty($persist)) {
                    $repo->saveEntities($persist);

                    // Free RAM
                    $this->em->clear('MauticCampaignBundle:LeadEventLog');
                }

                unset($persist);
            }

            // Free RAM
            $this->em->clear('MauticLeadBundle:Lead');
            $this->em->clear('MauticUserBundle:User');
            unset($events, $leads);

            $currentCount = ($max) ? $totalEventCount : $eventCount;
            if ($output && $currentCount < $maxCount) {
                $progress->setProgress($currentCount);
            }

            // Free some memory
            gc_collect_cycles();
        }

        if ($output) {
            $progress->finish();
            $output->writeln('');
        }

        return $processedEvents;
    }

    /**
     * Find and trigger the negative events, i.e. the events with a no decision path
     *
     * @param Campaign        $campaign
     * @param int             $totalEventCount
     * @param int             $limit
     * @param bool            $max
     * @param OutputInterface $output
     *
     * @return int
     */
    public function triggerNegativeEvents($campaign, &$totalEventCount = 0, $limit = 100, $max = false, OutputInterface $output = null)
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Triggering negative events');

        $campaignId   = $campaign->getId();
        $campaignName = $campaign->getName();

        $repo         = $this->getRepository();
        $campaignRepo = $this->getCampaignRepository();

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        // Get events to avoid large number of joins
        $campaignEvents = $repo->getCampaignEvents($campaignId);

        // Get an array of events that are non-action based
        $nonActionEvents = array();
        $actionEvents    = array();
        foreach ($campaignEvents as $id => $e) {
            if (!empty($e['decisionPath']) && $campaignEvents[$e['parent_id']]['eventType'] != 'condition') {
                if ($e['decisionPath'] == 'no') {
                    $nonActionEvents[$e['parent_id']][$id] = $e;
                } elseif ($e['decisionPath'] == 'yes') {
                    $actionEvents[$e['parent_id']][] = $id;
                }
            }
        }

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
                    array('%leads%' => $leadCount, '%batch%' => $limit)
                )
            );
        }

        $start = $eventCount = $leadProcessedCount = $lastRoundPercentage = $processedCount = 0;

        $eventSettings = $campaignModel->getEvents();

        $maxCount = ($max) ? $max : ($leadCount * count($nonActionEvents));

        // Try to save some memory
        gc_enable();

        if ($leadCount) {
            if ($output) {
                $progress = new ProgressBar($output, $maxCount);
                $progress->start();
                if ($max) {
                    $progress->advance($totalEventCount);
                }
            }

            $sleepBatchCount = 0;

            while ($start <= $leadCount) {
                // Get batched campaign ids
                $campaignLeads = $campaignRepo->getCampaignLeads($campaignId, $start, $limit, array(), array('cl.lead_id, cl.date_added'));

                $campaignLeadIds   = array();
                $campaignLeadDates = array();
                foreach ($campaignLeads as $r) {
                    $campaignLeadIds[]                = $r['lead_id'];
                    $campaignLeadDates[$r['lead_id']] = $r['date_added'];
                }

                unset($campaignLeads);

                foreach ($nonActionEvents as $parentId => $events) {
                    // Just a check to ensure this is an appropriate action
                    if ($campaignEvents[$parentId]['eventType'] != 'decision') {
                        $logger->debug('CAMPAIGN: Parent event ID #'.$parentId.' is not a decision.');

                        continue;
                    }

                    // Get only leads who have had the action prior to the decision executed
                    $grandParentId = $campaignEvents[$parentId]['parent_id'];

                    // Get the lead log for this batch of leads limiting to those that have already triggered
                    // the decision's parent and haven't executed this level in the path yet
                    if ($grandParentId) {
                        $logger->debug('CAMPAIGN: Checking for leads based on grand parent execution.');

                        $leadLog         = $repo->getEventLog($campaignId, $campaignLeadIds, array($grandParentId), array_keys($events));
                        $applicableLeads = array_keys($leadLog);
                    } else {
                        $logger->debug('CAMPAIGN: Checking for leads based on exclusion due to being at root level');

                        // The event has no grandparent (likely because the decision is first in the campaign) so find leads that HAVE
                        // already executed the events in the root level
                        $havingEvents      = (isset($actionEvents[$parentId])) ? array_merge($actionEvents[$parentId], array_keys($events)) : array_keys($events);
                        $leadLog           = $repo->getEventLog($campaignId, $campaignLeadIds, $havingEvents);
                        $unapplicableLeads = array_keys($leadLog);

                        // Only use leads that are not applicable
                        $applicableLeads = array_diff($campaignLeadIds, $unapplicableLeads);

                        unset($excludeLeads, $unapplicableLeads);
                    }

                    if (empty($applicableLeads)) {
                        $logger->debug('CAMPAIGN: No events are applicable');

                        continue;
                    }

                    $logger->debug('CAMPAIGN: These leads have have not gone down the positive path: '.implode(', ', $applicableLeads));

                    // Get the leads
                    $leads = $leadModel->getEntities(
                        array(
                            'filter'     => array(
                                'force' => array(
                                    array(
                                        'column' => 'l.id',
                                        'expr'   => 'in',
                                        'value'  => $applicableLeads
                                    )
                                )
                            ),
                            'orderBy'    => 'l.id',
                            'orderByDir' => 'asc'
                        )
                    );

                    if (!count($leads)) {
                        // Just a precaution in case non-existent leads are lingering in the campaign leads table

                        continue;
                    }

                    // Loop over the non-actions and determine if it has been processed for this lead

                    /** @var \Mautic\LeadBundle\Entity\Lead $lead */
                    foreach ($leads as $lead) {
                        // Set lead for listeners
                        $leadModel->setSystemCurrentLead($lead);

                        $logger->debug('CAMPAIGN: Lead ID #'.$lead->getId());

                        // Prevent path if lead has already gone down this path
                        if (!isset($leadLog[$lead->getId()]) || !array_key_exists($parentId, $leadLog[$lead->getId()])) {

                            // Get date to compare against
                            $utcDateString = ($grandParentId) ? $leadLog[$lead->getId()][$grandParentId]['date_triggered']
                                : $campaignLeadDates[$lead->getId()];

                            // Convert to local DateTime
                            $grandParentDate = $this->factory->getDate($utcDateString, 'Y-m-d H:i:s', 'UTC')->getLocalDateTime();

                            // Non-decision has not taken place yet, so cycle over each associated action to see if timing is right
                            $eventTiming   = array();
                            $executeAction = false;
                            foreach ($events as $id => $e) {
                                if ($sleepBatchCount == $limit) {
                                    // Keep CPU down
                                    $this->batchSleep();
                                    $sleepBatchCount = 0;
                                } else {
                                    $sleepBatchCount++;
                                }

                                if (isset($leadLog[$lead->getId()]) && array_key_exists($id, $leadLog[$lead->getId()])) {
                                    $logger->debug('CAMPAIGN: Event (ID #'.$id.') has already been executed');
                                    unset($e);
                                    continue;
                                }

                                if (!isset($eventSettings[$e['eventType']][$e['type']])) {
                                    $logger->debug('CAMPAIGN: Event (ID #'.$id.') no longer exists');
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
                                // Timing is not appropriate so move on to next lead
                                unset($eventTiming);
                                continue;
                            }

                            if ($max && ($totalEventCount + count($eventTiming)) >= $max) {

                                // Hit the max or will hit the max while mid-process for the lead
                                if ($output) {
                                    $progress->finish();
                                    $output->writeln('');
                                }

                                return $eventCount;
                            }

                            $decisionLogged = false;

                            // Execute or schedule events
                            foreach ($eventTiming as $id => $eventTriggerDate) {
                                // Set event
                                $event             = $events[$id];
                                $event['campaign'] = array(
                                    'id'   => $campaignId,
                                    'name' => $campaignName
                                );

                                // Set lead in case this is triggered by the system
                                $leadModel->setSystemCurrentLead($lead);

                                if ($this->executeEvent(
                                        $event,
                                        $campaign,
                                        $lead,
                                        $eventSettings,
                                        false,
                                        null,
                                        $eventTriggerDate,
                                        $processedCount,
                                        $totalEventCount
                                    )
                                    && !$decisionLogged
                                ) {
                                    // Log the decision
                                    $log = $this->getLogEntity($parentId, $campaign, $lead, null, true);
                                    $log->setDateTriggered(new \DateTime());
                                    $log->setNonActionPathTaken(true);
                                    $repo->saveEntity($log);
                                    $this->em->detach($log);
                                    unset($log);

                                    $decisionLogged = true;
                                }

                                $eventCount++;
                                $totalEventCount++;

                                unset($utcDateString, $grandParentDate);
                            }
                        } else {
                            $logger->debug('CAMPAIGN: Decision has already been executed.');
                        }

                        $currentCount = ($max) ? $totalEventCount : $leadProcessedCount;
                        if ($output && $currentCount < $maxCount) {
                            $progress->setProgress($currentCount);
                        }
                    }

                    // Save RAM
                    $this->em->detach($lead);
                    unset($lead);
                }

                // Next batch
                $start += $limit;

                $leadProcessedCount += count($campaignLeadIds);

                // Save RAM
                $this->em->clear('MauticLeadBundle:Lead');
                $this->em->clear('MauticUserBundle:User');

                unset($leads, $campaignLeadIds, $leadLog);

                $currentCount = ($max) ? $eventCount : $leadProcessedCount;
                if ($output && $currentCount < $maxCount) {
                    $progress->setProgress($currentCount);
                }

                // Free some memory
                gc_collect_cycles();
            }

            if ($output) {
                $progress->finish();
                $output->writeln('');
            }

        }

        return $processedCount;
    }

    /**
     * Invoke the event's callback function
     *
     * @param      $event
     * @param      $settings
     * @param null $lead
     * @param null $eventDetails
     * @param bool $systemTriggered
     *
     * @return bool|mixed
     */
    public function invokeEventCallback($event, $settings, $lead = null, $eventDetails = null, $systemTriggered = false)
    {
        $args = array(
            'eventSettings'   => $settings,
            'eventDetails'    => $eventDetails,
            'event'           => $event,
            'lead'            => $lead,
            'factory'         => $this->factory,
            'systemTriggered' => $systemTriggered,
            'config'          => $event['properties']
        );

        if (is_callable($settings['callback'])) {
            if (is_array($settings['callback'])) {
                $reflection = new \ReflectionMethod($settings['callback'][0], $settings['callback'][1]);
            } elseif (strpos($settings['callback'], '::') !== false) {
                $parts      = explode('::', $settings['callback']);
                $reflection = new \ReflectionMethod($parts[0], $parts[1]);
            } else {
                $reflection = new \ReflectionMethod(null, $settings['callback']);
            }

            $pass = array();
            foreach ($reflection->getParameters() as $param) {
                if (isset($args[$param->getName()])) {
                    $pass[] = $args[$param->getName()];
                } else {
                    $pass[] = null;
                }
            }

            $result = $reflection->invokeArgs($this, $pass);

            if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_EXECUTION)) {
                $this->dispatcher->dispatch(
                    CampaignEvents::ON_EVENT_EXECUTION,
                    new CampaignExecutionEvent($args, $result)
                );
            }
        } else {
            $result = true;
        }

        // Save some RAM for batch processing
        unset($args, $pass, $reflection, $settings, $lead, $event, $eventDetails);

        return $result;
    }

    /**
     * Check to see if the interval between events are appropriate to fire currentEvent
     *
     * @param           $action
     * @param \DateTime $parentTriggeredDate
     * @param bool      $allowNegative
     *
     * @return bool
     */
    public function checkEventTiming($action, \DateTime $parentTriggeredDate = null, $allowNegative = false)
    {
        $logger = $this->factory->getLogger();
        $now    = new \DateTime();

        $logger->debug('CAMPAIGN: Check timing for '.ucfirst($action['eventType']).' ID# '.$action['id']);

        if ($action instanceof Event) {
            $action = $action->convertToArray();
        }

        if ($action['decisionPath'] == 'no' && !$allowNegative) {
            $logger->debug('CAMPAIGN: '.ucfirst($action['eventType']).' is attached to a negative path which is not allowed');

            return false;
        } else {
            $negate = ($action['decisionPath'] == 'no' && $allowNegative);

            if ($action['triggerMode'] == 'interval') {
                $triggerOn = $negate ? $parentTriggeredDate : new \DateTime();

                if ($triggerOn == null) {
                    $triggerOn = new \DateTime();
                }

                $interval = $action['triggerInterval'];
                $unit     = strtoupper($action['triggerIntervalUnit']);

                $logger->debug('CAMPAIGN: Adding interval of '.$interval.$unit.' to '.$triggerOn->format('Y-m-d H:i:s T'));

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
                    $logger->debug(
                        'CAMPAIGN: Date to execute ('.$triggerOn->format('Y-m-d H:i:s T').') is later than now ('.$now->format('Y-m-d H:i:s T')
                        .') so schedule'
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

                $logger->debug('CAMPAIGN: Date execution on '.$action['triggerDate']->format('Y-m-d H:i:s T'));

                $pastDue = $now >= $action['triggerDate'];

                if ($negate) {
                    $logger->debug(
                        'CAMPAIGN: Negative comparison; Date to execute ('.$action['triggerDate']->format('Y-m-d H:i:s T').') compared to now ('.$now->format('Y-m-d H:i:s T').') and is thus '.(($pastDue) ? 'overdue': 'not past due'));

                    //it is past the scheduled trigger date and the lead has done nothing so return true to trigger
                    //the event otherwise false to do nothing
                    $return = ($pastDue) ? true : $action['triggerDate'];

                    // Save some RAM for batch processing
                    unset($now, $action);

                    return $return;
                } elseif (!$pastDue) {

                    $logger->debug(
                        'CAMPAIGN: Non-negative comparison; Date to execute ('.$action['triggerDate']->format('Y-m-d H:i:s T').') compared to now ('.$now->format('Y-m-d H:i:s T').') and is thus not past due'
                    );

                    //schedule the event
                    return $action['triggerDate'];
                }
            }
        }

        $logger->debug('CAMPAIGN: Nothing stopped execution based on timing.');

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
     * @throws \Doctrine\ORM\ORMException
     */
    public function getLogEntity($event, $campaign, $lead = null, $ipAddress = null, $systemTriggered = false)
    {
        $log = new LeadEventLog();

        if ($ipAddress == null) {
            // Lead triggered from system IP
            $ipAddress = $this->factory->getIpAddress();
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
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->factory->getModel('lead');
            $lead      = $leadModel->getCurrentLead();
        }
        $log->setLead($lead);
        $log->setDateTriggered(new \DateTime());
        $log->setSystemTriggered($systemTriggered);

        // Save some RAM for batch processing
        unset($event, $campaign, $lead);

        return $log;
    }

    /**
     * Batch sleep according to settings
     */
    protected function batchSleep()
    {
        $eventSleepTime = $this->factory->getParameter('batch_event_sleep_time', false);
        if ($eventSleepTime === false) {
            $eventSleepTime = $this->factory->getParameter('batch_sleep_time', 1);
        }

        if (empty($eventSleepTime)) {

            return;
        }

        if ($eventSleepTime < 1) {
            usleep($eventSleepTime * 1000);
        } else {
            sleep($eventSleepTime);
        }
    }
}
