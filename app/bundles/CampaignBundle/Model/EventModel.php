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
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CampaignBundle\Entity\Event;
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
                        $response = $this->invokeEventCallback($event, $settings, $lead, $eventDetails, $systemTriggered);
                        if ($response !== false) {
                            $logger->debug('CAMPAIGN: ID# '.$child['id'].' successfully executed and logged.');
                            $log = $this->getLogEntity($child['id'], $event['campaign']['id'], $lead, $ipAddress, $systemTriggered);

                            $childrenTriggered = true;

                            if ($response !== true) {
                                // Some feed back was given to be passed back to the function calling triggerEvent
                                $actionResponses[$type][$child['id']] = $response;
                                $log->setMetatdata($response);
                            }

                            $persist[] = $log;
                            unset($log);
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

        return $actionResponses;
    }

    /**
     * Trigger the root level action(s) in campaign(s)
     *
     * @param $campaign
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function triggerStartingEvents($campaign, &$totalEventCount, $limit = 100, $max = false, OutputInterface $output = null)
    {
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

        // Get a lead count
        $leadCount = $campaignRepo->getCampaignLeadCount($campaignId, $ignoreLeads);

        // Get a total number of events that will be processed
        $totalEvents = $leadCount * count($events);

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
        while ($continue && $eventCount < $maxCount) {
            // Get list of all campaign leads
            $campaignLeads = $campaignRepo->getCampaignLeadIds($campaignId, $start, $limit, $ignoreLeads);

            if (empty($campaignLeads)) {
                // No leads found

                break;
            }

            $leads = $leadModel->getEntities(
                array(
                    'filter'           => array(
                        'force' => array(
                            array(
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $campaignLeads
                            )
                        )
                    ),
                    'orderBy'          => 'l.id',
                    'orderByDir'       => 'asc'
                )
            );

            if (!count($leads)) {
                // Just a precaution in case non-existent leads are lingering in the campaign leads table

                break;
            }

            // Keep CPU down
            sleep(2);

            foreach ($leads as $lead) {
                sleep(1);

                // Keep CPU down
                usleep(500);

                $logger->debug('CAMPAIGN: Current Lead ID: '. $lead->getId());

                if ($eventCount >= $maxCount) {
                    break;
                }

                // Set lead in case this is triggered by the system
                $leadModel->setSystemCurrentLead($lead);

                foreach ($events as $event) {
                    $eventCount++;

                    if (!isset($eventSettings['action'][$event['type']])) {
                        unset($event);

                        continue;
                    }

                    // Set campaign ID
                    $event['campaign'] = array('id' => $campaignId);

                    $logger->debug('CAMPAIGN: Event ID# '.$event['id']);

                    $timing = $this->checkEventTiming($event, new \DateTime());
                    if ($timing instanceof \DateTime) {
                        $processedCount++;

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


                        //trigger the action
                        $response = $this->invokeEventCallback($event, $eventSettings['action'][$event['type']], $lead, null, true);
                        if ($response === false) {
                            // Something failed so remove the log
                            $repo->deleteEntity($log);

                            $logger->debug('CAMPAIGN: ID# '.$event['id'].' execution failed.');
                        } else {
                            $processedCount++;

                            if ($response !== true) {
                                $log->setMetatdata($response);
                                $repo->saveEntity($log);
                            }

                            $logger->debug('CAMPAIGN: ID# '.$event['id'].' successfully executed and logged.');
                        }

                    } else {
                        //else do nothing

                        $logger->debug('CAMPAIGN: Timing failed ('.gettype($timing).')');
                    }

                    $totalEventCount++;

                    if (!empty($log)) {
                        // Detach log
                        $this->em->detach($log);
                        unset($log);
                    }

                    unset($timing, $event);

                    if ($max && $eventCount >= $max) {
                        // Hit the max, bye bye
                        $continue = false;

                        break;
                    }
                }

                // Free some RAM
                $this->em->detach($lead);
                unset($lead);

                if ($output && $eventCount < $maxCount) {
                    $progress->setCurrent($eventCount);
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

        return $processedCount;
    }

    /**
     * @param                 $campaign
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

        $campaignId = $campaign->getId();

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
        $campaignEvents = $repo->getCampaignActionEvents($campaignId);

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
                $progress->setCurrent($totalEventCount);
            }
        }

        while ($eventCount < $totalScheduledCount) {
            // Get a count
            $events = $repo->getScheduledEvents($campaignId, false, $limit);

            if (empty($events)) {
                unset($campaignEvents, $event, $leads, $eventSettings);

                return $eventCount;
            }

            $leads = $leadModel->getEntities(
                array(
                    'filter'           => array(
                        'force' => array(
                            array(
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => array_keys($events)
                            )
                        )
                    ),
                    'orderBy'          => 'l.id',
                    'orderByDir'       => 'asc'
                )
            );

            foreach ($events as $leadId => $leadEvents) {
                if (!isset($leads[$leadId])) {
                    continue;
                }

                $lead = $leads[$leadId];

                $logger->debug('CAMPAIGN: Current Lead ID: '.$lead->getId());

                // Set lead in case this is triggered by the system
                $leadModel->setSystemCurrentLead($lead);

                $persist = array();

                foreach ($leadEvents as $log) {
                    // Keep CPU down
                    sleep(1);

                    $event = $campaignEvents[$log['event_id']];

                    // Set campaign ID
                    $event['campaign'] = array('id' => $campaignId);

                    if (!isset($eventSettings['action'][$event['type']])) {
                        unset($event);
                        $eventCount++;
                        $totalEventCount++;

                        continue;
                    }

                    //trigger the action
                    $response = $this->invokeEventCallback($event, $eventSettings['action'][$event['type']], $lead, null, true);
                    if ($response !== false) {
                        $processedEvents++;

                        $logger->debug('CAMPAIGN: ID# '.$event['id'].' successfully executed and logged.');

                        try {
                            $e = $this->em->getReference('MauticCampaignBundle:LeadEventLog', array('lead' => $leadId, 'event' => $event['id']));
                            $e->setTriggerDate(null);
                            $e->setIsScheduled(false);
                            $e->setDateTriggered(new \DateTime());

                            if ($response !== true) {
                                $e->setMetadata($response);
                            }

                            $persist[] = $e;
                        } catch (EntityNotFoundException $e) {
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
                $progress->setCurrent($currentCount);
            }

            // Free some memory
            gc_collect_cycles();
        }

        if($output) {
            $progress->finish();
            $output->writeln('');
        }

        return $processedEvents;
    }

    /**
     * Find and trigger the negative events, i.e. the events with a no decision path
     *
     * @param null $campaignId
     */
    public function triggerNegativeEvents($campaign, $totalEventCount = 0, $limit = 100, $max = false, OutputInterface $output = null)
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $logger = $this->factory->getLogger();
        $logger->debug('CAMPAIGN: Triggering negative events');

        $campaignId = $campaign->getId();

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
        foreach ($campaignEvents as $id => $e) {
            if ($e['decisionPath'] == 'no') {
                $nonActionEvents[$e['parent_id']][$id] = $e;
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

        $maxCount = ($max) ? $max : $leadCount;

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

            while ($start <= $leadCount) {
                // Keep CPU down
                sleep(1);

                // Get batched campaign ids
                $campaignLeads = $campaignRepo->getCampaignLeadIds($campaignId, $start, $limit);

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
                    $leadLog = $repo->getEventLog($campaignId, $campaignLeads, array($grandParentId), array_keys($events));

                    $applicableLeads = array_keys($leadLog);
                    if (empty($applicableLeads)) {
                        $logger->debug('CAMPAIGN: No events are applicable');

                        continue;
                    }

                    // Get the leads
                    $leads = $leadModel->getEntities(
                        array(
                            'filter'           => array(
                                'force' => array(
                                    array(
                                        'column' => 'l.id',
                                        'expr'   => 'in',
                                        'value'  => $applicableLeads
                                    )
                                )
                            ),
                            'orderBy'          => 'l.id',
                            'orderByDir'       => 'asc'
                        )
                    );

                    if (!count($leads)) {
                        // Somehow ran out of leads so break out
                        break;
                    }

                    $pauseBatchCount = 0;
                    $pauseBatch      = 5;

                    // Loop over the non-actions and determine if it has been processed for this lead
                    foreach ($leads as $l) {
                        // Set lead for listeners
                        $leadModel->setSystemCurrentLead($l);

                        $logger->debug('CAMPAIGN: Lead ID #'.$l->getId());

                        // Prevent path if lead has already gone down this path
                        if (!array_key_exists($parentId, $leadLog[$l->getId()])) {
                            if ($pauseBatchCount == $pauseBatch) {
                                // Keep CPU down
                                sleep(2);
                                $pauseBatchCount = 0;
                            } else {
                                $pauseBatchCount++;
                            }

                            // Get date to compare against
                            $utcDateString = $leadLog[$l->getId()][$grandParentId]['date_triggered'];
                            // Convert to local DateTime
                            $grandParentDate = $this->factory->getDate($utcDateString, 'Y-m-d H:i:s', 'UTC')->getLocalDateTime();

                            // Non-decision has not taken place yet, so cycle over each associated action to see if timing is right
                            $eventTiming   = array();
                            $executeAction = false;
                            foreach ($events as $id => $e) {
                                if (array_key_exists($id, $leadLog[$l->getId()])) {
                                    $logger->debug('CAMPAIGN: Event (ID #'.$id.') has already been executed');
                                    unset($e);
                                    continue;
                                }

                                if (!isset($eventSettings['action'][$e['type']])) {
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

                            $logDecision = $decisionLogged = false;

                            // Execute or schedule events
                            foreach ($eventTiming as $id => $timing) {
                                // Set event
                                $e             = $events[$id];
                                $e['campaign'] = array('id' => $campaignId);

                                // Set lead in case this is triggered by the system
                                $leadModel->setSystemCurrentLead($l);

                                if ($timing instanceof \DateTime) {
                                    $processedCount++;

                                    // Schedule the action
                                    $logger->debug(
                                        'CAMPAIGN: ID# '.$e['id'].' timing is not appropriate and thus scheduled for '.$timing->format(
                                            'Y-m-d H:m:i T'
                                        ).''
                                    );

                                    $log = $this->getLogEntity($e['id'], $campaign, $l, null, true);
                                    $log->setLead($l);
                                    $log->setIsScheduled(true);
                                    $log->setTriggerDate($timing);

                                    $repo->saveEntity($log);

                                    $logDecision = true;
                                } else {
                                    $processedCount++;

                                    // Save log first to prevent subsequent triggers from duplicating
                                    $log = $this->getLogEntity($e['id'], $campaign, $l, null, true);
                                    $log->setDateTriggered(new \DateTime());

                                    $repo->saveEntity($log);

                                    $response = $this->invokeEventCallback($e, $eventSettings['action'][$e['type']], $l, null, true);
                                    if ($response === false) {
                                        $repo->deleteEntity($log);
                                        $logger->debug('CAMPAIGN: ID# '.$e['id'].' execution failed.');

                                        $logDecision = true;
                                    } else {
                                        $logger->debug('CAMPAIGN: ID# '.$e['id'].' successfully executed and logged.');

                                        if ($response !== true) {
                                            $log->setMetatdata($response);
                                            $repo->saveEntity($log);
                                        }
                                    }
                                }

                                if (!empty($log)) {
                                    $this->em->detach($log);
                                }

                                unset($e, $log);

                                if ($logDecision && !$decisionLogged) {
                                    // Log the decision
                                    $log = $this->getLogEntity($parentId, $campaign, $l, null, true);
                                    $log->setDateTriggered(new \DateTime());
                                    $log->setNonActionPathTaken(true);
                                    $repo->saveEntity($log);
                                    $this->em->detach($log);
                                    unset($log);

                                    $decisionLogged = true;
                                }

                                if ($max && $totalEventCount >= $max) {
                                    // Hit the max
                                    if ($output) {
                                        $progress->finish();
                                        $output->writeln('');
                                    }

                                    return $eventCount;
                                }

                                $eventCount++;
                                $totalEventCount++;

                                unset($utcDateString, $grandParentDate);
                            }

                        } else {
                            $logger->debug('CAMPAIGN: Decision has already been executed.');

                            $pauseBatchCount++;

                            if ($pauseBatchCount == $pauseBatch) {
                                // Keep CPU down
                                sleep(2);
                                $pauseBatchCount = 0;
                            }
                        }

                        $currentCount = ($max) ? $totalEventCount : $leadProcessedCount;
                        if ($output && $currentCount < $maxCount) {
                            $progress->setCurrent($currentCount);
                        }

                    }

                    // Save RAM
                    $this->em->detach($l);
                    unset($l);
                }

                // Next batch
                $start += $limit;

                $leadProcessedCount += count($campaignLeads);

                // Save RAM
                $this->em->clear('MauticLeadBundle:Lead');
                $this->em->clear('MauticUserBundle:User');

                unset($leads, $campaignLeads, $leadLog);

                $currentCount = ($max) ? $eventCount : $leadProcessedCount;
                if ($output && $currentCount < $maxCount) {
                    $progress->setCurrent($currentCount);
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

        // Save some RAM for batch processing
        unset($args, $pass, $reflection, $settings, $lead, $event, $eventDetails);

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

                $logger->debug('CAMPAIGN: Interval delay of '.$interval.$unit);

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

                $logger->debug('CAMPAIGN: Comparison of triggerOn >= now ('.$triggerOn->format('Y-m-d H:i:s').' >= '.$now->format('Y-m-d H:i:s'));

                if ($triggerOn > $now) {
                    // Save some RAM for batch processing
                    unset($now, $action, $dv, $dt);

                    //the event is to be scheduled based on the time interval
                    return $triggerOn;
                }
            } elseif ($action['triggerMode'] == 'date') {
                $logger->debug('CAMPAIGN: Date execution on '.$action['triggerDate']->format('Y-m-d H:i:s'));

                $pastDue = $now >= $action['triggerDate'];

                if ($negate) {
                    $logger->debug(
                        'CAMPAIGN: Negate comparison of triggerDate >= now ('.$action['triggerDate']->format('Y-m-d H:i:s').' >= '.$now->format(
                            'Y-m-d H:i:s'
                        )
                    );

                    //it is past the scheduled trigger date and the lead has done nothing so return true to trigger
                    //the event otherwise false to do nothing
                    $return = ($pastDue) ? true : $action['triggerDate'];

                    // Save some RAM for batch processing
                    unset($now, $action);

                    return $return;
                } elseif (!$pastDue) {
                    $logger->debug(
                        'CAMPAIGN: Non-negate comparison of triggerDate >= now ('.$action['triggerDate']->format('Y-m-d H:i:s').' >= '.$now->format(
                            'Y-m-d H:i:s'
                        )
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

        // Save some RAM for batch processing
        unset($event, $campaign, $lead);

        return $log;
    }
}
