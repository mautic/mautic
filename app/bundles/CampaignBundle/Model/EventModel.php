<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CampaignBundle\Entity\Event;

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
     * @param       $eventType
     * @param mixed $passthrough
     * @param mixed $eventTypeId
     *
     * @return bool|mixed
     */
    public function triggerEvent ($eventType, $passthrough = null, $eventTypeId = null)
    {
        static $leadCampaigns = array(), $eventList = array(), $availableEvents = array(), $leadsEvents = array(), $examinedEvents = array();

        //only trigger events for anonymous users
        if (!$this->security->isAnonymous()) {
            return false;
        }

        if ($eventTypeId !== null && $this->factory->getEnvironment() == 'prod') {
            //let's prevent some unnecessary DB calls
            $session         = $this->factory->getSession();
            $triggeredEvents = $session->get('mautic.triggered.campaign.events', array());
            if (in_array($eventTypeId, $triggeredEvents)) {
                return false;
            }
            $triggeredEvents[] = $eventTypeId;
            $session->set('mautic.triggered.campaign.events', $triggeredEvents);
        }

        //get the current lead
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');
        $lead      = $leadModel->getCurrentLead();

        //get the lead's campaigns so we have when the lead was added
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $this->factory->getModel('campaign');
        if (empty($leadCampaigns)) {
            $leadCampaigns = $campaignModel->getLeadCampaigns($lead);
        }

        //get the events for the trigger
        $eventRepo = $this->getRepository();
        if (empty($eventList[$eventType])) {
            $eventList[$eventType] = $eventRepo->getPublishedByType($eventType);
        }
        $events = $eventList[$eventType];

        //get event settings from the bundles
        if (empty($availableEvents)) {
            $availableEvents = $campaignModel->getEvents();
        }

        //make sure there are events before continuing
        if (!count($availableEvents) || empty($events)) {
            return false;
        }

        //get a list of events that has already been performed on this lead
        if (empty($leadsEvents)) {
            $leadsEvents = $eventRepo->getLeadTriggeredEvents($lead->getId());
        }

        //IP address for the log
        /** @var \Mautic\CoreBundle\Entity\IpAddress $ipAddress */
        $ipAddress = $this->factory->getIpAddress();

        //Store all the entities to be persisted so that it can be done at one time
        $persist = array();

        //leads can enter dripflows at any point therefore all matching trigger events are pulled
        //because of this, the events examined must be tracked to prevent double processing of events
        foreach ($events as $campaignId => $campaignEvents) {
            $lastEvent = false;

            foreach ($campaignEvents as $k => $event) {
                $settings = $availableEvents[$event['eventType']][$eventType];

                //has this event already been examined via the parent's children?
                if (in_array($event['id'], $examinedEvents)) {
                    continue;
                }
                $examinedEvents[] = $event['id'];

                //check the callback function for the event to make sure it even applies based on its settings
                if (!$this->invokeEventCallback($event, $settings, $lead, $passthrough)) {
                    continue;
                }

                if (!isset($leadsEvents[$event['id']])) {
                    //log the trigger
                    $log = new LeadEventLog();
                    $log->setIpAddress($ipAddress);
                    $log->setEvent($this->em->getReference('MauticCampaignBundle:Event', $event['id']));
                    $log->setLead($lead);
                    $log->setDateTriggered(new \DateTime());
                    $persist[] = $log;

                    $leadsEvents[$event['id']] = $event;
                }

                //add the date of when the lead was added to this campaign
                $event['stats']['campaign']['dateAdded'] = !empty($leadCampaigns[$event['campaign']['id']]['leads']) ?
                    $leadCampaigns[$event['campaign']['id']]['leads'][0]['dateAdded'] : new \DateTime();
                //add the date of when the event was invoked triggered if applicable
                $event['stats']['event']['dateTriggered'] = (isset($leadsEvents[$event['id']]['log'])) ?
                    $leadsEvents[$event['id']]['log'][0]['dateTriggered'] : new \DateTime();

                if (!empty($event['children'])) {
                    foreach ($event['children'] as $child) {
                        $settings = $availableEvents[$child['eventType']][$child['type']];

                        //add the date of when the lead was added to this campaign
                        $child['stats']['campaign']['dateAdded'] = !empty($leadCampaigns[$child['campaign']['id']]['leads']) ?
                            $leadCampaigns[$child['campaign']['id']]['leads'][0]['dateAdded'] : new \DateTime();
                        //add the date of when the event was invoked triggered if applicable
                        $child['stats']['event']['dateTriggered'] = (isset($leadsEvents[$child['id']]['log'])) ?
                            $leadsEvents[$child['id']]['log'][0]['dateTriggered'] : new \DateTime();

                        $examinedEvents[] = $child['id'];

                        if (isset($leadsEvents[$child['id']])) {
                            //this child event has already been fired for this lead as well so move on to the next

                            //set the last event for comparison
                            $lastEvent = $child;

                            continue;
                        } elseif ($child['eventType'] == 'outcome') {
                            //have hit the next trigger in the drip flow so continue on to the next matching trigger type
                            break;
                        }

                        list ($timeAppropriate, $triggerOn) = $this->checkEventTiming($lastEvent, $child);
                        if (!$timeAppropriate) {
                            //schedule and move on to the next action

                            $log = new LeadEventLog();
                            $log->setIpAddress($ipAddress);
                            $log->setEvent($this->em->getReference('MauticCampaignBundle:Event', $child['id']));
                            $log->setLead($lead);
                            $log->setIsScheduled(true);
                            $log->setTriggerDate($triggerOn);

                            $persist[] = $log;

                            continue;
                        }

                        //trigger the action
                        if ($this->invokeEventCallback($child, $settings, $lead, $passthrough)) {
                            //add the lead to the campaign
                            if (!isset($leadCampaigns[$child['campaign']['id']])) {
                                $campaignModel->addLead(
                                    $this->em->getReference('MauticCampaignBundle:Campaign', $child['campaign']['id']),
                                    $lead
                                );
                                //set the campaign is now assigned to the lead
                                $leadCampaigns[$child['campaign']['id']] = $child['campaign'];
                                //set that this event has now been called
                                $leadsEvents[$child['id']] = $child;
                            }

                            $log = new LeadEventLog();
                            $log->setIpAddress($ipAddress);
                            $log->setEvent($this->em->getReference('MauticCampaignBundle:Event', $child['id']));
                            $log->setLead($lead);
                            $log->setDateTriggered(new \DateTime());
                            $persist[] = $log;
                        }

                        //set the last event for comparison
                        $lastEvent = $child;
                    }
                }

                //set the last event for comparison
                $lastEvent = $event;
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
     * Invoke the event's callback function
     *
     * @param $event
     * @param $settings
     * @param $lead
     * @param $passthrough
     *
     * @return bool|mixed
     */
    public function invokeEventCallback ($event, $settings, $lead, $passthrough = null)
    {
        $args = array(
            'passthrough' => $passthrough,
            'event'       => $event,
            'lead'        => $lead,
            'factory'     => $this->factory
        );

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
     * @param $previousEvent
     * @param $currentEvent
     * @param $leadsEvents
     *
     * @return bool
     */
    public function checkEventTiming ($previousEvent, $currentEvent)
    {
        $now = new \DateTime();
        switch ($currentEvent['triggerMode']) {
            case 'interval':
                //use the previous event date if applicable; if not, then use the date the lead was added to the campaign
                $triggerOn = (is_array($previousEvent) && !empty($previousEvent['stats']['event']['dateTriggered'])) ?
                    $previousEvent['stats']['event']['dateTriggered'] : $currentEvent['stats']['campaign']['dateAdded'];

                $interval     = $currentEvent['triggerInterval'];
                $intervalUnit = $currentEvent['triggerIntervalUnit'];
                $dv           = new \DateInterval("P{$interval}" . strtoupper($intervalUnit));
                $triggerOn->add($dv);

                //is the date plus the interval greater than or equal to today?
                if ($triggerOn > $now) {
                    return array(false, $triggerOn);
                }
            case 'date':
                if ($currentEvent['triggerDate'] > $now) {
                    return array(false, $currentEvent['triggerDate']);
                }
            default:
                return array(true, null);
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
        $availableEvents = $campaignModel->getEvents();
        $persist         = array();

        foreach ($events as $e) {
            /** @var \Mautic\CampaignBundle\Entity\Event $event */
            $event     = $e->getEvent();
            $eventType = $event->getEventType();
            $type      = $event->getType();
            if (!isset($availableEvents[$eventType][$type])) {
                continue;
            }

            $settings = $availableEvents[$eventType][$type];

            $lead = $e->getLead();

            //trigger the action
            if ($this->invokeEventCallback($event->convertToArray(), $settings, $lead)) {
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
}