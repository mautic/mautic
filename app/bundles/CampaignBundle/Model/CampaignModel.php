<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

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
    public function getRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Campaign');
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\EventRepository
     */
    public function getEventRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Event');
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\LeadRepository
     */
    public function getCampaignLeadRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Lead');
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
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
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
     * @return null|object
     */
    public function getEntity($id = null)
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
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
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
     * @param       $sessionEvents
     * @param       $sessionOrder
     */
    public function setEvents(Campaign &$entity, $sessionEvents, $sessionOrder, $deletedEvents)
    {
        $existingEvents = $entity->getEvents();

        foreach ($deletedEvents as $deleteMe) {
            if (isset($existingEvents[$deleteMe])) {
                $entity->removeEvent($existingEvents[$deleteMe]);
            }
        }

        //set the events from session
        $events = array();
        foreach ($sessionEvents as $id => $properties) {
            $isNew = (!empty($properties['id']) && isset($existingEvents[$properties['id']])) ? false : true;
            $event = !$isNew ? $existingEvents[$properties['id']] : new Event();

            foreach ($properties as $f => $v) {
                if (in_array($f, array('id', 'order', 'parent')))
                    continue;

                $func = "set" .  ucfirst($f);
                if (method_exists($event, $func)) {
                    $event->$func($v);
                }
                $event->setCampaign($entity);
                $events[$id] = $event;
            }
        }

        //determine and set the order and also parent which must be done after the entity has been created and
        //monitored by doctrine
        $byParent = array();
        $setParent = function($eventId, $parentId) use (&$byParent, $entity, $events, $deletedEvents) {
            //check to see if this event has a parent that has been deleted
            $atTopParent   = false;
            $parentDeleted = false;
            $parent        = $events[$eventId]->getParent();
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
                return;
            }

            if ($parentId != 'null') {
                if (isset($events[$parentId])) {
                    $events[$eventId]->setParent($events[$parentId]);
                }
            } else {
                $events[$eventId]->removeParent();

                //save parentId as itself for setting the order
                $parentId = $eventId;
            }

            $byParent[$parentId][] = $eventId;
        };

        if (!empty($sessionOrder)) {
            //the entities have been reordered manually by user
            foreach ($sessionOrder as $child => $parent) {
                if (!isset($events[$child])) {
                    //likely a deleted event
                    continue;
                }

                $setParent($child, $parent);
            }
        } else {
            //the entities were not reordered by user
            foreach ($events as $id => $e) {
                //set the parent order
                $parent   = $e->getParent();
                $parentId = ($parent === null) ? 'null' : $parent->getId();

                $setParent($id, $parentId);
            }
        }

        //set the order
        $parentCount = 1;
        foreach ($byParent as $parentId => $children) {
            $events[$parentId]->setOrder($parentCount);
            $entity->addEvent($parentId, $events[$parentId]);

            $childCount = $parentCount + 0.01;
            foreach ($children as $childId) {
                if ($childId != $parentId) {
                    $events[$childId]->setOrder($childCount);
                    $childCount += 0.01;
                    $entity->addEvent($childId, $events[$childId]);
                }
            }

            $parentCount++;
        }
    }

    /**
     * Gets array of custom events from bundles subscribed CampaignEvents::CAMPAIGN_ON_BUILD
     * @return mixed
     */
    public function getEvents()
    {
        static $events;

        if (empty($events)) {
            //build them
            $events = array();
            $event  = new Events\CampaignBuilderEvent($this->translator);
            $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_BUILD, $event);
            $events['action']  = $event->getActions();
            $events['trigger'] = $event->getTriggers();
        }
        return $events;
    }

    /**
     * Proxy for EventModel::triggerEvent
     *
     * @param      $eventType
     * @param mixed $passthrough
     * @param mixed $eventTypeId
     */
    public function triggerEvent($eventType, $passthrough = null, $eventTypeId = null)
    {
        return $this->factory->getModel('campaign.event')->triggerEvent($eventType, $passthrough, $eventTypeId);
    }

    /**
     * Gets the campaigns a specific lead is part of
     *
     * @param Lead $lead
     * @param bool $forList
     */
    public function getLeadCampaigns(Lead $lead = null, $forList = false)
    {
        static $campaigns = array();

        if ($lead === null) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel       = $this->factory->getModel('lead');
            $lead            = $leadModel->getCurrentLead();
        }

        if (!isset($campaigns[$lead->getId()])) {
            $campaigns[$lead->getId()] = $this->getRepository()->getPublishedCampaigns(null, $lead->getId(), $forList);
        }

        return $campaigns[$lead->getId()];
    }

    /**
     * Gets a list of published campaigns
     *
     * @param Lead $lead
     * @param bool $forList
     */
    public function getPublishedCampaigns($forList = false)
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
     * @param Lead     $lead
     */
    public function addLead(Campaign $campaign, Lead $lead)
    {
        $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
            'lead'     => $lead,
            'campaign' => $campaign
        ));

        if ($campaignLead != null) {
            //already exists
            return;
        }

        $campaignLead = new \Mautic\CampaignBundle\Entity\Lead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());

        $campaign->addLead($lead->getId(), $campaignLead);

        $this->saveEntity($campaign);

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
    public function addLeads(Campaign $campaign, array $leads)
    {
        foreach ($leads as $lead) {

            if (!$lead instanceof Lead) {
                $leadId = $lead;
                $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
            }

            $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
                'lead'     => $lead,
                'campaign' => $campaign
            ));

            if ($campaignLead != null) {
                //already exists
                unset($campaignLead);
                continue;
            }

            $campaignLead = new \Mautic\CampaignBundle\Entity\Lead();
            $campaignLead->setCampaign($campaign);
            $campaignLead->setDateAdded(new \DateTime());
            $campaignLead->setLead($lead);
            $campaign->addLead($lead->getId(), $campaignLead);

            if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
                $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'added');
                $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
            }
            unset($campaignLead);
        }

        $this->saveEntity($campaign);
    }

    /**
     * Remove lead from the campaign
     *
     * @param Campaign $campaign
     * @param Lead     $lead
     */
    public function removeLead(Campaign $campaign, Lead $lead)
    {
        $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
            'lead'     => $lead,
            'campaign' => $campaign
        ));

        if (!$campaignLead) {
            return;
        }

        $campaign->removeLead($campaignLead);

        $this->saveEntity($campaign);

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
     */
    public function removeLeads(Campaign $campaign, array $leads)
    {
        foreach ($leads as $lead) {

            if (!$lead instanceof Lead) {
                $leadId = $lead;
                $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
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

            $campaign->removeLead($campaignLead);

            if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
                $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'removed');
                $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
            }
        }

        $this->saveEntity($campaign);
    }
}