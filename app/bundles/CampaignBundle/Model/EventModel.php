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
    public function getRepository ()
    {
        return $this->em->getRepository('MauticCampaignBundle:Event');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase ()
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
    public function getEntity ($id = null)
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
    public function deleteEvents ($currentEvents, $originalEvents, $deletedEvents)
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
    public function buildEventHierarchy ($entity, &$hierarchy)
    {
        if ($entity instanceof Event) {
            $children = $entity->getChildren();
            $id       = $entity->getId();
        } else {
            $children = $entity['children'];
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
    public function triggerEvent ($type, $eventDetails = null, $typeId = null)
    {
        static $leadCampaigns = array(), $eventList = array(), $availableEvents = array(), $leadsEvents = array(), $examinedEvents = array();

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Campaign triggered for event type ' . $type);
        //only trigger events for anonymous users (to prevent populating full of user/company data)
        if (!$this->security->isAnonymous()) {
            $logger->debug('CAMPAIGN: lead not anonymous; abort');
            return false;
        }

        if ($typeId !== null && $this->factory->getEnvironment() == 'prod') {
            //let's prevent some unnecessary DB calls
            $session         = $this->factory->getSession();
            $triggeredEvents = $session->get('mautic.triggered.campaign.events', array());
            if (in_array($typeId, $triggeredEvents)) {
                return false;
            }
            $triggeredEvents[] = $typeId;
            $session->set('mautic.triggered.campaign.events', $triggeredEvents);
        }

        //get the current lead
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');
        $lead      = $leadModel->getCurrentLead();
        $logger->debug('CAMPAIGN: Current Lead ID: ' . $lead->getId());

        //get the lead's campaigns so we have when the lead was added
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');
        if (empty($leadCampaigns)) {
            $leadCampaigns = $campaignModel->getLeadCampaigns($lead, true);
        }

        //get the list of events that match the triggering event and is in the campaigns this lead belongs to
        /** @var \Mautic\CampaignBundle\Entity\EventRepository $eventRepo */
        $eventRepo = $this->getRepository();
        if (empty($eventList[$type])) {
            $eventList[$type] = $eventRepo->getPublishedByType($type, $leadCampaigns, $lead->getId());
        }
        $events = $eventList[$type];

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
        if (empty($leadsEvents)) {
            $leadsEvents = $eventRepo->getLeadTriggeredEvents($lead->getId());
        }

        //IP address for the log
        /** @var \Mautic\CoreBundle\Entity\IpAddress $ipAddress */
        $ipAddress = $this->factory->getIpAddress();

        //Store all the entities to be persisted so that it can be done at one time
        $persist = array();

        foreach ($events as $campaignId => $campaignEvents) {
            foreach ($campaignEvents as $k => $event) {
                //check to see if this has been fired sequentially
                if (!empty($event['parent'])) {
                    if (!isset($leadsEvents[$event['parent']['id']])) {
                        //this event has a parent that has not been triggered for this lead so break out
                        $logger->debug('CAMPAIGN: parent (ID# ' . $event['parent']['id'] . ') for ID# ' . $event['id'] . ' has not been triggered yet; abort');
                        break;
                    }
                    $parentLog = $leadsEvents[$event['parent']['id']];

                    if ($parentLog['isScheduled']) {
                        //this event has a parent that is scheduled and thus not triggered
                        $logger->debug('CAMPAIGN: parent (ID# ' . $event['parent']['id'] . ') for ID# ' . $event['id'] . ' has not been triggered yet because it\'s scheduled; abort');
                    } else {
                        $parentTriggeredDate = $parentLog['dateTriggered'];
                    }
                } else {
                    $parentTriggeredDate = new \DateTime();
                }

                $settings = $availableEvents[$event['eventType']][$type];

                //has this event already been examined via a parent's children?
                //all events of this triggering type has to be queried since this particular event could be anywhere in the dripflow
                if (in_array($event['id'], $examinedEvents)) {
                    $logger->debug('CAMPAIGN: ID# ' . $event['id'] . ' already processed this round; continue');
                    continue;
                }
                $examinedEvents[] = $event['id'];

                //check the callback function for the event to make sure it even applies based on its settings
                if (!$this->invokeEventCallback($event, $settings, $lead, $eventDetails)) {
                    $logger->debug('CAMPAIGN: ID# ' . $event['id'] . ' callback check failed; continue');
                    continue;
                }

                if (!empty($event['children'])) {
                    $childrenTriggered = false;
                    foreach ($event['children'] as $child) {
                        if (isset($leadsEvents[$child['id']])) {
                            //this child event has already been fired for this lead so move on to the next event
                            $logger->debug('CAMPAIGN: ID# ' . $child['id'] . ' already triggered; continue');
                            continue;
                        } elseif ($child['eventType'] != 'action') {
                            //hit a triggering type event so move on
                            $logger->debug('CAMPAIGN: ID# ' . $child['id'] . ' is an action; continue');
                            continue;
                        }

                        $settings = $availableEvents[$child['eventType']][$child['type']];

                        //store in case a child was pulled with events
                        $examinedEvents[] = $child['id'];

                        $timing = $this->checkEventTiming($child, $parentTriggeredDate);
                        if ($timing instanceof \DateTime) {
                            //lead actively triggered this event, a decision wasn't involved, or it was system triggered and a "no" path so schedule the event to be fired at the defined time
                            $logger->debug('CAMPAIGN: ID# ' . $child['id'] . ' timing is not appropriate and thus scheduled for ' . $timing . '; continue');

                            $log = $this->getLogEntity($child['id'], $event['campaign']['id'], $lead, $ipAddress);
                            $log->setIsScheduled(true);
                            $log->setTriggerDate($timing);
                            $persist[] = $log;

                            $childrenTriggered = true;
                            continue;
                        } elseif (!$timing) {
                            //timing not appropriate and should not be scheduled so bail
                            $logger->debug('CAMPAIGN: ID# ' . $child['id'] . '  timing is not appropriate and not scheduled.');
                            continue;
                        }

                        //trigger the action
                        if ($this->invokeEventCallback($child, $settings, $lead, $eventDetails)) {
                            $logger->debug('CAMPAIGN: ID# ' . $child['id'] . ' successfully executed and logged.');
                            $persist[] = $this->getLogEntity($child['id'], $event['campaign']['id'], $lead, $ipAddress);

                            $childrenTriggered = true;
                        } else {
                            $logger->debug('CAMPAIGN: ID# ' . $child['id'] . ' execution failed.');
                        }
                    }

                    if ($childrenTriggered) {
                        //a child of this event was triggered or scheduled so make not of the triggering event in the log
                        $persist[] = $this->getLogEntity($event['id'], $event['campaign']['id'], $lead, $ipAddress);
                    }
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
     * Trigger the first action in a campaign if a decision is not involved
     *
     * @param $campaign
     * @param $event
     * @param $settings
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function triggerCampaignStartingAction($campaign, $eventEntity, $settings)
    {
        //convert to array to match triggerEvent
        $event = $eventEntity->convertToArray();
        $event['campaign'] = $campaign->convertToArray();

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');
        $eventId       = $event['id'];

        $leads = $campaignModel->getCampaignLeads($campaign, $eventId);

        foreach ($leads as $campaignLead) {
            $lead = $campaignLead->getLead();

            $timing = $this->checkEventTiming($event, new \DateTime(), true);
            if ($timing instanceof \DateTime) {
                $log = $this->getLogEntity($event['id'], $campaign, $lead, null, true);
                $log->setLead($lead);
                $log->setIsScheduled(true);
                $log->setTriggerDate($timing);

                $persist[] = $log;
            } elseif ($timing && $this->invokeEventCallback($event, $settings, $lead, true)) {
                $log = $this->getLogEntity($event['id'], $campaign, $lead, null, true);
                $log->setDateTriggered(new \DateTime());

                $persist[] = $log;
            } //else do nothing
        }

        if (!empty($persist)) {
            $this->getRepository()->saveEntities($persist);
        }
    }

    /**
     * Trigger events that are scheduled
     *
     * @param mixed $campaignId
     */
    public function triggerScheduledEvents ($campaignId = null)
    {
        $repo            = $this->getRepository();
        $events          = $repo->getPublishedScheduled($campaignId);
        $campaignModel   = $this->factory->getModel('campaign');
        $leadModel       = $this->factory->getModel('lead');
        $availableEvents = $campaignModel->getEvents();
        $persist         = array();

        foreach ($events as $e) {
            /** @var \Mautic\CampaignBundle\Entity\Event $event */
            $event     = $e['event'];
            $eventType = $event['eventType'];
            $type      = $event['type'];
            if (!isset($availableEvents[$eventType][$type])) {
                continue;
            }

            $settings = $availableEvents[$eventType][$type];

            $lead = $leadModel->getEntity($e['lead']['id']);

            //trigger the action
            if ($this->invokeEventCallback($event, $settings, $lead, null, true)) {
                $e->setTriggerDate(null);
                $e->setIsScheduled(false);
                $e->setDateTriggered(new \DateTime());
                $persist[] = $e;
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
    public function triggerNegativeEvents ($campaignId = null)
    {
        //get a list of pending events
        $events          = $this->getRepository()->getNegativePendingEvents($campaignId);
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel   = $this->factory->getModel('campaign');
        /** @var \Mautic\CampaignBundle\Model\EventModel $eventModel */
        $eventModel      = $this->factory->getModel('campaign.event');
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel       = $this->factory->getModel('lead');
        $eventRepo       = $eventModel->getRepository();

        $logger          = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Triggering negative events');

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
                    if (empty($leadsEventCache[$lead['lead_id']])) {
                        $leadsEventCache[$lead['lead_id']] = $eventRepo->getLeadTriggeredEvents($lead['lead_id']);
                    }

                    $leadsEvents = $leadsEventCache[$lead['lead_id']];

                    if (empty($event['parent'])) {
                        $logger->debug('CAMPAIGN: ID# ' . $event['id'] . ' has no parent and thus not applicable.');
                        continue;
                    }

                    //the grandparent (parent's parent) is what will be compared against to determine if the timeframe is appropriate
                    $grandparent = $event['parent']['parent'];

                    if (empty($grandparent)) {
                        //there is no grandparent so compare using the date added to the campaign
                        $fromDate = $lead['dateAdded'];
                    } else {
                        if (!isset($leadsEvents[$grandparent['id']])) {
                            $logger->debug('CAMPAIGN: grandparent ID# ' . $grandparent['id'] . ' <- parent ID# ' . $event['parent']['id'] . ' <- ID# ' . $event['id'] . ' has not been triggered; continue');
                            continue;
                        }

                        $grandparentLog = $leadsEvents[$grandparent['id']]['log'][0];
                        if ($grandparentLog['isScheduled']) {
                            //this event has a parent that is scheduled and thus not triggered
                            $logger->debug('CAMPAIGN: grandparent ID# ' . $grandparent['id'] . ' <- parent ID# ' . $event['parent']['id'] . ' <- ID# ' . $event['id'] . ' is scheduled; continue');
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

                        if ($this->invokeEventCallback($event, $settings, $leadEntityCache[$lead['lead_id']], true)) {
                            $logger->debug('CAMPAIGN: ID# ' . $event['id'] . ' execution successful and logged.');

                            $log = $this->getLogEntity($event['id'], $event['campaign']['id'], $leadEntityCache[$lead['lead_id']], null, true);
                            $log->setDateTriggered(new \DateTime());

                            $persist[] = $log;
                        } else {
                            $logger->debug('CAMPAIGN: ID# ' . $event['id'] . ' execution failed.');
                        }//else do nothing
                    } else {
                        $logger->debug('CAMPAIGN: Timing not appropriate for ID# ' . $event['id']);
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
    public function invokeEventCallback ($event, $settings, $lead = null, $eventDetails = null, $systemTriggered = false)
    {
        $args = array(
            'eventDetails'    => $eventDetails,
            'event'           => $event,
            'lead'            => $lead,
            'factory'         => $this->factory,
            'systemTriggered' => $systemTriggered
        );

        if ($lead instanceof Lead) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->factory->getModel('lead');
            $lead->setFields($leadModel->getLeadDetails($lead));
        }

        if (is_callable($settings['callback'])) {
            if (is_array($settings['callback'])) {
                $reflection = new \ReflectionMethod($settings['callback'][0], $settings['callback'][1]);
            } elseif (strpos($settings['callback'], '::') !== false) {
                $parts      = explode('::', $settings['callback']);
                $reflection = new \ReflectionMethod($parts[0], $parts[1]);
            } else {
                new \ReflectionMethod(null, $settings['callback']);
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
    public function checkEventTiming ($action, $parentTriggeredDate = null, $systemTriggered = false)
    {
        $now = new \DateTime();

        if ($action instanceof Event) {
            $action = $action->convertToArray();
        }

        $negate = ($action['decisionPath'] == 'no' && $systemTriggered);

        if ($action['triggerMode'] == 'interval') {

            $triggerOn = $negate ? $parentTriggeredDate : new \DateTime();

            if ($triggerOn == null) {
                $triggerOn = new \DateTime();
            }

            $interval = $action['triggerInterval'];
            $unit     = strtoupper($action['triggerIntervalUnit']);

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

                return ($now >= $triggerOn) ? true : false;
            } else {
                $triggerOn->add($dv);

                if ($triggerOn > $now) {
                    //the event is to be scheduled based on the time interval
                    return $triggerOn;
                }
            }
        } elseif ($action['triggerMode'] == 'date') {
            $pastDue = $action['triggerDate'] >= $now;
            if ($negate) {
                //it is past the scheduled trigger date and the lead has done nothing so return true to trigger
                //the event otherwise false to do nothing
                return ($pastDue) ? true : false;
            } elseif (!$pastDue) {
                //schedule the event
                return $action['triggerDate'];
            }
        }

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
            $lead = $leadModel->getCurrentLead();
        }
        $log->setLead($lead);
        $log->setDateTriggered(new \DateTime());
        $log->setSystemTriggered($systemTriggered);
        return $log;
    }
}
