<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\LeadBundle\Entity\Lead;
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
        static $leadCampaigns = array(), $eventList = array(), $availableEvents = array(), $leadsEvents = array(), $examinedEvents = array();

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Campaign triggered for event type '.$type.'('.$typeId.')');

        // Skip the anonymous check to force actions to fire for subsequant triggers
        $systemTriggered = defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED');

        //only trigger events for anonymous users (to prevent populating full of user/company data)
        if (!$systemTriggered && !$this->security->isAnonymous()) {
            $logger->debug('CAMPAIGN: lead not anonymous; abort');

            return false;
        }

        if ($typeId !== null && $this->factory->getEnvironment() == 'prod') {
            //let's prevent some unnecessary DB calls
            $session         = $this->factory->getSession();
            $triggeredEvents = $session->get('mautic.triggered.campaign.events', array());
            if (in_array($typeId, $triggeredEvents)) {
                $logger->debug('CAMPAIGN: '.$typeId.' has already been processed.');

                return false;
            }
            $triggeredEvents[] = $typeId;
            $session->set('mautic.triggered.campaign.events', $triggeredEvents);
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

        if (empty($leadCampaigns[$leadId] )) {
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
        if (empty($availableEvents)) {
            $availableEvents = $campaignModel->getEvents();
        }

        //make sure there are events before continuing
        if (!count($availableEvents) || empty($events)) {
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

                if (isset($availableEvents[$event['eventType']][$type])) {
                    $settings = $availableEvents[$event['eventType']][$type];
                } else {
                    // Not found maybe it's no longer available?
                    $logger->debug('CAMPAIGN: '.$type.' does not exist. (#'.$event['id'].')');

                    continue;
                }

                //check the callback function for the event to make sure it even applies based on its settings
                if (!$this->invokeEventCallback($event, $settings, $lead, $eventDetails, $systemTriggered)) {
                    $logger->debug('CAMPAIGN: ID# '.$event['id'].' callback check failed');
                    continue;
                } else {
                    $logger->debug('CAMPAIGN: ID# '.$event['id'].' successfully executed and logged.');
                }

                if (!empty($event['children'])) {
                    $logger->debug('CAMPAIGN: ID# '.$event['id'].' has children');

                    $childrenTriggered = false;
                    foreach ($event['children'] as $child) {
                        if (isset($leadsEvents[$leadId][$child['id']])) {
                            //this child event has already been fired for this lead so move on to the next event
                            $logger->debug('CAMPAIGN: ID# '.$child['id'].' already triggered');
                            continue;
                        } elseif ($child['eventType'] != 'action') {
                            //hit a triggering type event so move on
                            $logger->debug('CAMPAIGN: ID# '.$child['id'].' is a decision');
                            continue;
                        } else {
                            $logger->debug('CAMPAIGN: ID# '.$child['id'].' is being processed');
                        }

                        if (isset($availableEvents[$child['eventType']][$child['type']])) {
                            $settings = $availableEvents[$child['eventType']][$child['type']];
                        } else {
                            // Not found maybe it's no longer available?
                            $logger->debug('CAMPAIGN: '.$child['type'].' does not exist. (#'.$child['id'].')');

                            continue;
                        }

                        //store in case a child was pulled with events
                        $examinedEvents[$leadId][] = $child['id'];

                        $timing = $this->checkEventTiming($child, $parentTriggeredDate);
                        if ($timing instanceof \DateTime) {
                            //lead actively triggered this event, a decision wasn't involved, or it was system triggered and a "no" path so schedule the event to be fired at the defined time
                            $logger->debug(
                                'CAMPAIGN: ID# '.$child['id'].' timing is not appropriate and thus scheduled for '.$timing->format('Y-m-d H:m:i T').''
                            );

                            $log = $this->getLogEntity($child['id'], $event['campaign']['id'], $lead, $ipAddress, $systemTriggered);
                            $log->setIsScheduled(true);
                            $log->setTriggerDate($timing);
                            $persist[] = $log;

                            $childrenTriggered = true;
                            continue;
                        } elseif (!$timing) {
                            //timing not appropriate and should not be scheduled so bail
                            $logger->debug('CAMPAIGN: ID# '.$child['id'].'  timing is not appropriate and not scheduled.');
                            continue;
                        }

                        //trigger the action
                        if ($this->invokeEventCallback($child, $settings, $lead, $eventDetails, $systemTriggered)) {
                            $logger->debug('CAMPAIGN: ID# '.$child['id'].' successfully executed and logged.');
                            $persist[] = $this->getLogEntity($child['id'], $event['campaign']['id'], $lead, $ipAddress, $systemTriggered);

                            $childrenTriggered = true;

                        } else {
                            $logger->debug('CAMPAIGN: ID# '.$child['id'].' execution failed.');
                        }
                    }

                    if ($childrenTriggered) {
                        //a child of this event was triggered or scheduled so make not of the triggering event in the log
                        $persist[] = $this->getLogEntity($event['id'], $event['campaign']['id'], $lead, $ipAddress, $systemTriggered);
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
    }

    /**
     * Trigger the root level action(s) in campaign(s)
     *
     * @param $campaign
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function triggerStartingEvents($campaign, $limit = 100, $max = false, OutputInterface $output = null)
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $campaignId = $campaign->getId();

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Triggering starting events');

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        $repo = $this->getRepository();

        $events = $repo->getRootLevelActions($campaignId);

        if (empty($events)) {
            $logger->debug('CAMPAIGN: No events to trigger');

            return 0;
        }

        // Event settings
        $eventSettings = $campaignModel->getEvents();

        // Get a list of leads who have already had the events executed
        // (going to assume if one event of this level has fired for the event, all were fired)
        $ignoreLeads = $repo->getEventLogLeads(array_keys($events));

        // Get list of all campaign leads
        $campaignLeads = $this->em->getRepository('MauticCampaignBundle:Campaign')->getCampaignLeadIds($campaignId, $ignoreLeads);
        if (empty($campaignLeads)) {
            $logger->debug('CAMPAIGN: No leads to process');

            unset($events);

            return 0;
        }

        $leadCount = count($campaignLeads);

        $output->writeln(
            $this->translator->trans(
                'mautic.campaign.trigger.lead_count',
                array('%leads%' => $leadCount, '%batch%' => $limit)
            )
        );

        $eventCount = 0;

        while ($eventCount < $leadCount) {
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
                    'orderByDir' => 'asc',
                    'limit'      => $limit
                )
            );

            // Remove retrieved ids from $campaignLeads for next batch
            $campaignLeads = array_slice($campaignLeads, $limit);

            foreach ($events as $event) {
                // Set campaign ID
                $event['campaign'] = array('id' => $campaignId);

                // Keep CPU down
                sleep(2);

                $logger->debug('CAMPAIGN: Event ID# '.$event['id']);

                foreach ($leads as $lead) {

                    $logger->debug('CAMPAIGN: Current Lead ID: '.$lead->getId());

                    // Set lead in case this is triggered by the system
                    $leadModel->setSystemCurrentLead($lead);

                    $timing = $this->checkEventTiming($event, new \DateTime());
                    if ($timing instanceof \DateTime) {
                        //lead actively triggered this event, a decision wasn't involved, or it was system triggered and a "no" path so schedule the event to be fired at the defined time
                        $logger->debug(
                            'CAMPAIGN: ID# '.$event['id'].' timing is not appropriate and thus scheduled for '.$timing->format('Y-m-d H:m:i T').''
                        );

                        $log = $this->getLogEntity($event['id'], $campaign, $lead, null, true);
                        $log->setLead($lead);
                        $log->setIsScheduled(true);
                        $log->setTriggerDate($timing);

                        $repo->saveEntity($log);
                    } elseif ($timing) {
                        // Save log first to prevent subsequent triggers from duplicating
                        $log = $this->getLogEntity($event['id'], $campaign, $lead, null, true);
                        $log->setDateTriggered(new \DateTime());
                        $repo->saveEntity($log);

                        if (!$this->invokeEventCallback($event, $eventSettings['action'][$event['type']], $lead, null, true)) {
                            // Something failed so remove the log
                            $repo->deleteEntity($log);

                            $logger->debug('CAMPAIGN: ID# '.$event['id'].' execution failed.');
                        } else {
                            $logger->debug('CAMPAIGN: ID# '.$event['id'].' successfully executed and logged.');
                        }

                    } else {
                        //else do nothing

                        $logger->debug('CAMPAIGN: Timing failed ('.gettype($timing).')');
                    }

                    if (!empty($log)) {
                        // Detach log
                        $this->em->detach($log);

                        $eventCount++;
                    }

                    if ($max && $eventCount >= $max) {
                        // Hit the max, bye bye

                        return $eventCount;
                    }
                }
            }

            // Keep CPU down and give small amount of time for events to finish persisting
            // before detaching all the entities
            sleep(2);

            $this->em->clear('MauticLeadBundle:Lead');

            unset($leads);
        }

        return $eventCount;
    }

    /**
     * Trigger events that are scheduled
     *
     * @param mixed $campaignId
     */
    public function triggerScheduledEvents($campaignId = null)
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Triggering scheduled events');

        $repo            = $this->getRepository();
        $events          = $repo->getPublishedScheduled($campaignId);
        $campaignModel   = $this->factory->getModel('campaign');
        $leadModel       = $this->factory->getModel('lead');
        $availableEvents = $campaignModel->getEvents();
        $persist         = array();
        $leadEntityCache = array();

        if (empty($events)) {
            $logger->debug('CAMPAIGN: No events to trigger');
        }

        foreach ($events as $e) {
            /** @var \Mautic\CampaignBundle\Entity\Event $event */
            $event     = $e['event'];
            $eventType = $event['eventType'];
            $type      = $event['type'];
            if (!isset($availableEvents[$eventType][$type])) {
                continue;
            }

            $settings = $availableEvents[$eventType][$type];

            if (empty($leadEntityCache[$e['lead']['id']])) {
                $leadEntityCache[$e['lead']['id']] = $leadModel->getEntity($e['lead']['id']);
            }

            $logger->debug('CAMPAIGN: Current Lead ID: '.$e['lead']['id']);

            // Set the system lead for events that may fire as a result of firing this one
            $leadModel->setSystemCurrentLead($leadEntityCache[$e['lead']['id']]);

            //trigger the action
            if ($this->invokeEventCallback($event, $settings, $leadEntityCache[$e['lead']['id']], null, true)) {
                $logger->debug('CAMPAIGN: ID# '.$event['id'].' successfully executed and logged.');

                $e = $this->em->getReference('MauticCampaignBundle:LeadEventLog', array('lead' => $e['lead']['id'], 'event' => $event['id']));
                $e->setTriggerDate(null);
                $e->setIsScheduled(false);
                $e->setDateTriggered(new \DateTime());
                $persist[] = $e;
            } else {
                $logger->debug('CAMPAIGN: ID# '.$event['id'].' execution failed.');
            }
        }

        if (!empty($persist)) {
            $this->getRepository()->saveEntities($persist);
        }
    }

    /**
     * Find and trigger the negative events, i.e. the events with a no decision path
     *
     * @param null $campaignId
     */
    public function triggerNegativeEvents($campaignId = null)
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        //get a list of pending events
        $events = $this->getRepository()->getNegativePendingEvents($campaignId);
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');
        /** @var \Mautic\CampaignBundle\Model\EventModel $eventModel */
        $eventModel = $this->factory->getModel('campaign.event');
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');
        $eventRepo = $eventModel->getRepository();

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Triggering negative events');

        if (empty($events)) {
            $logger->debug('CAMPAIGN: No events to trigger');
        }

        //get settings for events
        $availableEvents = $campaignModel->getEvents();

        //used to cache stuff to prevent duplicate db calls
        $leadsEventCache = array();
        $leadEntityCache = array();

        //persist all entities at once
        $persist = array();

        foreach ($events as $event) {
            $settings = $availableEvents[$event['eventType']][$event['type']];

            if (!empty($event['campaign']['leads'])) {
                foreach ($event['campaign']['leads'] as $lead) {
                    $logger->debug('CAMPAIGN: Current Lead ID: '.$lead['lead_id']);

                    if (empty($leadsEventCache[$lead['lead_id']])) {
                        $leadsEventCache[$lead['lead_id']] = $eventRepo->getLeadTriggeredEvents($lead['lead_id']);
                    }

                    $leadsEvents = $leadsEventCache[$lead['lead_id']];

                    if (empty($event['parent'])) {
                        $logger->debug('CAMPAIGN: ID# '.$event['id'].' has no parent and thus not applicable.');
                        continue;
                    }

                    //the grandparent (parent's parent) is what will be compared against to determine if the timeframe is appropriate
                    $grandparent = $event['parent']['parent'];

                    if (empty($grandparent)) {
                        //there is no grandparent so compare using the date added to the campaign
                        $fromDate = $lead['dateAdded'];
                    } else {
                        if (!isset($leadsEvents[$grandparent['id']])) {
                            $logger->debug(
                                'CAMPAIGN: grandparent ID# '.$grandparent['id'].' <- parent ID# '.$event['parent']['id'].' <- ID# '.$event['id']
                                .' has not been triggered; continue'
                            );
                            continue;
                        }

                        $grandparentLog = $leadsEvents[$grandparent['id']]['log'][0];
                        if ($grandparentLog['isScheduled']) {
                            //this event has a parent that is scheduled and thus not triggered
                            $logger->debug(
                                'CAMPAIGN: grandparent ID# '.$grandparent['id'].' <- parent ID# '.$event['parent']['id'].' <- ID# '.$event['id']
                                .' is scheduled; continue'
                            );
                            continue;
                        } else {
                            $fromDate = $grandparentLog['dateTriggered'];
                        }
                    }

                    $timing = $this->checkEventTiming($event, $fromDate, true);
                    if ($timing) {
                        if (empty($leadEntityCache[$lead['lead_id']])) {
                            $leadEntityCache[$lead['lead_id']] = $leadModel->getEntity($lead['lead_id']);
                        }

                        // Set the system lead for events that may fire as a result of firing this one
                        $leadModel->setSystemCurrentLead($leadEntityCache[$lead['lead_id']]);

                        if ($this->invokeEventCallback($event, $settings, $leadEntityCache[$lead['lead_id']], null, true)) {
                            $logger->debug('CAMPAIGN: ID# '.$event['id'].' successfully executed and logged.');

                            $log = $this->getLogEntity($event['id'], $event['campaign']['id'], $leadEntityCache[$lead['lead_id']], null, true);
                            $log->setDateTriggered(new \DateTime());

                            $persist[] = $log;
                        } else {
                            $logger->debug('CAMPAIGN: ID# '.$event['id'].' execution failed.');
                        }//else do nothing
                    } else {
                        $logger->debug('CAMPAIGN: Timing not appropriate for ID# '.$event['id']);
                    }//else do nothing
                }
            }
        }

        if (!empty($persist)) {
            $this->getRepository()->saveEntities($persist);
        }
    }

    /**
     * Invoke the event's callback function
     *
     * @param $event
     * @param $settings
     * @param $lead
     * @param $eventDetails
     *
     * @return bool|mixed
     */
    public function invokeEventCallback($event, $settings, $lead = null, $eventDetails = null, $systemTriggered = false)
    {
        $args = array(
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
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Check to see if the interval between events are appropriate to fire currentEvent
     *
     * @param $triggeredEvent
     * @param $action
     *
     * @return bool
     */
    public function checkEventTiming($action, $parentTriggeredDate = null, $allowNegate = false)
    {
        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Determining timing for event execution');

        $now = new \DateTime();

        if ($action instanceof Event) {
            $action = $action->convertToArray();
        }

        if ($action['decisionPath'] == 'no' && !$allowNegate) {
            $logger->debug('CAMPAIGN: Action is in a no path and negate is not allowed');

            return false;
        } else {
            $negate = ($action['decisionPath'] == 'no' && $allowNegate);

            if ($action['triggerMode'] == 'interval') {
                $triggerOn = $negate ? $parentTriggeredDate : new \DateTime();

                if ($triggerOn == null) {
                    $triggerOn = new \DateTime();
                }

                $interval = $action['triggerInterval'];
                $unit     = strtoupper($action['triggerIntervalUnit']);

                $logger->debug('CAMPAIGN: Interval delay of ' . $interval . $unit);

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

                if ($negate) {
                    //if the set interval has passed since the parent event was triggered, then return true to trigger
                    //the event; otherwise false to do nothing
                    $now->sub($dv);

                    $logger->debug('CAMPAIGN: Negate comparison of now >= triggerOn (' . $now->format('Y-m-d H:i:s') . ' >= ' . $triggerOn->format('Y-m-d H:i:s'));

                    return ($now >= $triggerOn) ? true : false;
                } else {
                    $triggerOn->add($dv);

                    $logger->debug('CAMPAIGN: Non-negate comparison of triggerOn >= now (' . $triggerOn->format('Y-m-d H:i:s') . ' >= ' . $now->format('Y-m-d H:i:s'));

                    if ($triggerOn > $now) {
                        //the event is to be scheduled based on the time interval
                        return $triggerOn;
                    }
                }
            } elseif ($action['triggerMode'] == 'date') {
                $logger->debug('CAMPAIGN: Date execution on ' . $action['triggerDate']->format('Y-m-d H:i:s'));

                $pastDue = $action['triggerDate'] >= $now;
                if ($negate) {
                    $logger->debug('CAMPAIGN: Negate comparison of triggerDate >= now (' . $action['triggerDate']->format('Y-m-d H:i:s') . ' >= ' . $now->format('Y-m-d H:i:s'));


                    //it is past the scheduled trigger date and the lead has done nothing so return true to trigger
                    //the event otherwise false to do nothing
                    return ($pastDue) ? true : false;
                } elseif (!$pastDue) {
                    $logger->debug('CAMPAIGN: Non-negate comparison of triggerDate >= now (' . $action['triggerDate']->format('Y-m-d H:i:s') . ' >= ' . $now->format('Y-m-d H:i:s'));

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
     * @param Event                                    $event
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

        return $log;
    }
}
