<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Doctrine\ORM\PersistentCollection;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\CampaignEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class CampaignModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class CampaignModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\CampaignRepository
     */
    public function getRepository ()
    {
        return $this->em->getRepository('MauticCampaignBundle:Campaign');
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\EventRepository
     */
    public function getEventRepository ()
    {
        return $this->em->getRepository('MauticCampaignBundle:Event');
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\LeadRepository
     */
    public function getCampaignLeadRepository ()
    {
        return $this->em->getRepository('MauticCampaignBundle:Lead');
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
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm ($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Campaign) {
            throw new MethodNotAllowedHttpException(array('Campaign'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();

        return $formFactory->create('campaign', $entity, $params);
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
            return new Campaign();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent ($action, &$entity, $isNew = false, $event = false)
    {
        if ($entity instanceof \Mautic\CampaignBundle\Entity\Lead) {
            return;
        }

        if (!$entity instanceof Campaign) {
            throw new MethodNotAllowedHttpException(array('Campaign'));
        }

        switch ($action) {
            case "pre_save":
                $name = CampaignEvents::CAMPAIGN_PRE_SAVE;
                break;
            case "post_save":
                $name = CampaignEvents::CAMPAIGN_POST_SAVE;
                break;
            case "pre_delete":
                $name = CampaignEvents::CAMPAIGN_PRE_DELETE;
                break;
            case "post_delete":
                $name = CampaignEvents::CAMPAIGN_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\CampaignEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return false;
        }
    }

    /**
     * @param Campaign $entity
     * @param          $sessionEvents
     * @param          $sessionConnections
     * @param          $deletedEvents
     */
    public function setEvents (Campaign &$entity, $sessionEvents, $sessionConnections, $deletedEvents)
    {
        $existingEvents = $entity->getEvents();

        //set the events from session
        $events = array();
        foreach ($sessionEvents as $id => $properties) {
            $isNew = (!empty($properties['id']) && isset($existingEvents[$properties['id']])) ? false : true;
            $event = !$isNew ? $existingEvents[$properties['id']] : new Event();

            foreach ($properties as $f => $v) {
                if ($f == 'id' && strpos($v, 'new') === 0) {
                    //set the temp ID used to be able to match up connections
                    $event->setTempId($v);
                }

                if (in_array($f, array('id', 'order', 'parent')))
                    continue;

                $func = "set" . ucfirst($f);
                if (method_exists($event, $func)) {
                    $event->$func($v);
                }
                $event->setCampaign($entity);
                $events[$id] = $event;
            }
        }


        foreach ($deletedEvents as $deleteMe) {
            if (isset($existingEvents[$deleteMe])) {
                $entity->removeEvent($existingEvents[$deleteMe]);
                unset($events[$deleteMe]);
            }
        }

        $hierarchy = array();

        //set parents which must be done after the entity has been created and monitored by doctrine
        $setParent = function ($eventId, $parentId, $anchor) use (&$hierarchy, $entity, $events, $deletedEvents) {
            //check to see if this event has a parent that has been deleted
            $atTopParent   = false;
            $parentDeleted = false;
            $parent        = $events[$eventId]->getParent();

            $events[$eventId]->setDecisionPath($anchor);

            while (!$atTopParent && !$parentDeleted) {
                if ($parent === null) {
                    $atTopParent = true;
                } else {
                    //has this parent been deleted?
                    if (in_array($parent->getId(), $deletedEvents)) {
                        $parentDeleted = true;
                    } else {
                        //check to see if this parent has a parent of its own
                        $parent = $events[$parent->getId()]->getParent();
                    }
                }
            }

            if ($parentDeleted) {
                //parent has been deleted so don't save this event
                $entity->removeEvent($events[$eventId]);
                unset($events[$eventId]);

                return;
            }

            if ($parentId != 'null') {
                if (isset($events[$parentId])) {
                    $events[$eventId]->setParent($events[$parentId]);
                }
            } else {
                $events[$eventId]->removeParent();
            }

            $hierarchy[$eventId] = $parentId;
        };

        $tempIds = array();
        foreach ($events as $id => $e) {
            if (isset($sessionConnections[$id])) {
                foreach ($sessionConnections[$id] as $sourceEndpoint => $children) {
                    foreach ($children as $child => $targetEndpoint) {
                        $setParent($child, $id, (in_array($sourceEndpoint, array('yes', 'no')) ? $sourceEndpoint : null));
                    }
                }
            } else {
                //set the parent order
                $parent   = $e->getParent();
                $parentId = ($parent === null) ? 'null' : $parent->getId();

                $setParent($id, $parentId, $e->getDecisionPath());
            }

            $tempIds[$e->getTempId()] = $id;
        }

        //loop again now to cleanup endpoints
        foreach ($events as $id => $e) {
            //cleanup endpoints while here
            $canvasSettings = $e->getCanvasSettings();
            if (isset($canvasSettings['endpoints'])) {
                foreach ($canvasSettings['endpoints'] as $sourceEndpoint => &$targets) {
                    foreach ($targets as $targetId => $targetEndpoint) {
                        //check to see if there are both a temp ID and ID for target
                        if (strpos($targetId, 'new') !== false && isset($targets[$tempIds[$targetId]])) {
                            //campaign has been edited
                            unset($targets[$targetId]);
                        }
                    }
                }
            }
            $e->setCanvasSettings($canvasSettings);
        }

        //set event order used when querying the events
        $this->buildOrder($hierarchy, $events, $entity);

        uasort($events, function ($a, $b) {
            $aOrder = $a->getOrder();
            $bOrder = $b->getOrder();
            if ($aOrder == $bOrder) {
                return 0;
            }

            return ($aOrder < $bOrder) ? -1 : 1;
        });

        return $events;
    }

    /**
     * @param        $hierarchy
     * @param        $events
     * @param        $entity
     * @param string $root
     * @param int    $order
     */
    private function buildOrder ($hierarchy, &$events, &$entity, $root = 'null', $order = 1)
    {
        foreach ($hierarchy as $eventId => $parent) {
            if ($parent == $root) {
                $events[$eventId]->setOrder($order);
                $entity->addEvent($eventId, $events[$eventId]);

                unset($hierarchy[$eventId]);

                $this->buildOrder($hierarchy, $events, $entity, $eventId, $order + 1);
            }
        }
    }

    /**
     * Gets array of custom events from bundles subscribed CampaignEvents::CAMPAIGN_ON_BUILD
     *
     * @return mixed
     */
    public function getEvents ()
    {
        static $events;

        if (empty($events)) {
            //build them
            $events = array();
            $event  = new Events\CampaignBuilderEvent($this->translator);
            $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_BUILD, $event);
            $events['decision']     = $event->getLeadDecisions();
            $events['systemaction'] = $event->getSystemChanges();
            $events['action']       = $event->getActions();
        }

        return $events;
    }

    /**
     * Proxy for EventModel::triggerEvent
     *
     * @param       $eventType
     * @param mixed $passthrough
     * @param mixed $eventTypeId
     */
    public function triggerEvent ($eventType, $passthrough = null, $eventTypeId = null)
    {
        /** @var \Mautic\CampaignBundle\Model\EventModel $eventModel */
        $eventModel = $this->factory->getModel('campaign.event');

        return $eventModel->triggerEvent($eventType, $passthrough, $eventTypeId);
    }

    /**
     * Gets the campaigns a specific lead is part of
     *
     * @param Lead $lead
     * @param bool $forList
     */
    public function getLeadCampaigns (Lead $lead = null, $forList = false)
    {
        static $campaigns = array();
        $leadModel = $this->factory->getModel('lead');

        if ($lead === null) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $lead = $leadModel->getCurrentLead();
        }

        if (!isset($campaigns[$lead->getId()])) {
            $repo   = $this->getRepository();
            $leadId = $lead->getId();
            //get the campaigns the lead is currently part of
            $campaigns[$leadId] = $repo->getPublishedCampaigns(null, $lead->getId(), $forList);
        }

        return $campaigns[$lead->getId()];
    }

    /**
     * Gets a list of published campaigns
     *
     * @param Lead $lead
     * @param bool $forList
     */
    public function getPublishedCampaigns ($forList = false)
    {
        static $campaigns = array();

        if (empty($campaigns)) {
            $campaigns = $this->getRepository()->getPublishedCampaigns(null, null, $forList);
        }

        return $campaigns;
    }

    /**
     * Add lead to the campaign
     *
     * @param Campaign $campaign
     * @param          $lead
     */
    public function addLead (Campaign $campaign, $lead)
    {
        if (!$lead instanceof Lead) {
            $leadId = (isset($lead['id'])) ? $lead['id'] : $lead;
            $lead = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        }

        $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
            'lead'     => $lead,
            'campaign' => $campaign
        ));

        if ($campaignLead != null) {
            if ($campaignLead->wasManuallyRemoved()) {
                $campaignLead->setManuallyRemoved(false);
                $this->saveEntity($campaignLead, false);
            } else {
                return;
            }
        } else {
            $campaignLead = new \Mautic\CampaignBundle\Entity\Lead();
            $campaignLead->setCampaign($campaign);
            $campaignLead->setLead($lead);
            $campaignLead->setDateAdded(new \DateTime());
            $campaign->addLead($lead->getId(), $campaignLead);

            $this->saveEntity($campaign, false);
        }

        if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
            $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'added');
            $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
        }
    }

    /**
     * Add lead(s) to the campaign
     *
     * @param Campaign $campaign
     * @param array    $leads
     */
    public function addLeads (Campaign $campaign, array $leads)
    {
        foreach ($leads as $lead) {

            if (!$lead instanceof Lead) {
                $leadId = (isset($lead['id'])) ? $lead['id'] : $lead;
                $lead = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
            }

            $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
                'lead'     => $lead,
                'campaign' => $campaign
            ));

            if ($campaignLead != null) {
                if ($campaignLead->wasManuallyAdded()) {
                    $campaignLead->setManuallyAdded(false);
                    $this->saveEntity($campaignLead, false);
                }
            } else {
                $campaignLead = new \Mautic\CampaignBundle\Entity\Lead();
                $campaignLead->setCampaign($campaign);
                $campaignLead->setDateAdded(new \DateTime());
                $campaignLead->setLead($lead);
                $campaign->addLead($lead->getId(), $campaignLead);
            }

            if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
                $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'added');
                $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
            }
            unset($campaignLead);
        }

        $this->saveEntity($campaign, false);
    }

    /**
     * Remove lead from the campaign
     *
     * @param Campaign $campaign
     * @param          $lead
     * @param bool     $manuallyRemoved
     */
    public function removeLead (Campaign $campaign, $lead, $manuallyRemoved = false)
    {
        if (!$lead instanceof Lead) {
            $leadId = (isset($lead['id'])) ? $lead['id'] : $lead;
            $lead = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        }

        $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
            'lead'     => $lead,
            'campaign' => $campaign
        ));

        if (!$campaignLead) {
            return;
        }

        if ($manuallyRemoved) {
            //do not remove the lead rather just mark it removed so that it does not get added back via a list
            $campaignLead->setManuallyRemoved(true);
            $this->saveEntity($campaignLead, false);
        } else {
            $campaign->removeLead($campaignLead);
            $this->saveEntity($campaign, false);
        }

        if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
            $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'removed');
            $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
        }
    }

    /**
     * Remove lead(s) from the campaign
     *
     * @param Campaign $campaign
     * @param array    $leads
     * @param bool     $manuallyRemoved
     */
    public function removeLeads (Campaign $campaign, array $leads, $manuallyRemoved = false)
    {
        foreach ($leads as $lead) {

            if (!$lead instanceof Lead) {
                $leadId = (isset($lead['id'])) ? $lead['id'] : $lead;
                $lead = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
            }

            $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
                'lead'     => $lead,
                'campaign' => $campaign
            ));

            if ($campaignLead == null) {
                //doesn't exist
                unset($campaignLead);
                continue;
            }

            if ($manuallyRemoved) {
                //do not remove the lead rather just mark it removed so that it does not get added back via a list
                $campaignLead->setManuallyRemoved(true);
                $this->saveEntity($campaignLead, false);
            } else {
                $campaign->removeLead($campaignLead);
            }

            if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
                $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'removed');
                $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
            }
        }

        $this->saveEntity($campaign, false);
    }

    /**
     * Get event log for a campaign
     *
     * @param      $campaign
     * @param null $event
     * @param null $lead
     *
     * @return mixed
     */
    public function getEventLog ($campaign, $event = null, $leads = null)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;
        if (is_array($event)) {
            $eventId = $event['id'];
        } elseif ($event instanceof Event) {
            $eventId = $event->getId();
        } else {
            $eventId = $event;
        }

        if ($leads instanceof PersistentCollection) {
            $leads = array_keys($leads->toArray());
        }

        return $this->em->getRepository('MauticCampaignBundle:LeadEventLog')->getCampaignLog($campaignId, $eventId, $leads);
    }

    /**
     * Get details of leads in a campaign
     *
     * @param      $campaign
     * @param null $leads
     *
     * @return mixed
     */
    public function getLeadDetails ($campaign, $leads = null)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;

        if ($leads instanceof PersistentCollection) {
            $leads = array_keys($leads->toArray());
        }

        return $this->em->getRepository('MauticCampaignBundle:Lead')->getLeadDetails($campaignId, $leads);
    }

    /**
     * @param      $campaign
     * @param bool $unlock
     */
    public function saveEntity ($campaign, $unlock = true)
    {
        parent::saveEntity($campaign, $unlock);

        //update leads
        $changes = $campaign->getChanges();
        $removed = (!empty($changes['lists']) && isset($changes['lists']['removed'])) ? $changes['lists']['removed'] : null;
        $this->buildCampaignLeads($campaign, $removed);
    }

    /**
     * @param      $campaign
     * @param null $removedLists
     */
    public function buildCampaignLeads ($campaign, $removedLists = null)
    {
        $lists = $campaign->getLists();

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $this->factory->getModel('lead.list');
        $leads     = $listModel->getLeadsByList($lists);

        //prevent duplicates
        $examinedLeads = array();

        foreach ($leads as $list => $listLeads) {
            foreach ($listLeads as $l) {
                if (in_array($l['id'], $examinedLeads)) {
                    continue;
                }
                $examinedLeads[] = $l['id'];

                $this->addLead($campaign, $l);
            }
        }

        if ($removedLists != null) {
            $campaignListIds = array_keys($lists->toArray());
            $leads           = $listModel->getLeadsByList($removedLists);

            $examinedLeads = array();
            $listRepo      = $this->em->getRepository('MauticLeadBundle:LeadList');

            foreach ($leads as $list => $listLeads) {
                //keyed by lead id then list id
                $listsByLeads = $listRepo->getLeadLists(array_keys($listLeads));

                foreach ($listLeads as $l) {
                    if (in_array($l['id'], $examinedLeads)) {
                        continue;
                    }
                    $examinedLeads[] = $l['id'];

                    //does this lead belong to another list still in the campaign?
                    $leadsLists = $listsByLeads[$l['id']];

                    if (array_intersect(array_keys($leadsLists), $campaignListIds)) {
                        continue;
                    }

                    $this->removeLead($campaign, $l);
                }
            }
        }

        $this->getRepository()->saveEntity($campaign, false);
    }

    /**
     * @param $campaign
     */
    public function getCampaignLeads($campaign)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;

        $leads = $this->em->getRepository('MauticCampaignBundle:Lead')->getLeads($campaignId);

        return $leads;
    }
}