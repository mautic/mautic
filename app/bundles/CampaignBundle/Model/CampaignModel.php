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
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignEvent;
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
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Campaign');
    }

    public function getEventRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:CampaignEvent');
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
     * {@inheritdoc}
     *
     * @param  \Mautic\CampaignBundle\Entity\Campaign $entity
     * @param  bool                               $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        $isNew = ($entity->getId()) ? false : true;

        parent::saveEntity($entity, $unlock);

        //should we campaign for existing leads?
        if ($entity->gettriggerExistingLeads() && $entity->isPublished()) {
            $events    = $entity->getEvents();
            $repo      = $this->getRepository();
            $persist   = array();
            $ipAddress = $this->factory->getIpAddress();

            foreach ($events as $event) {
                $dateTime  = $this->factory->getDate($entity->getDateAdded());
                $filter = array('force' => array(
                    array(
                        'column' => 'l.date_added',
                        'expr'   => 'lte',
                        'value'  => $dateTime->toUtcString()
                    ),
                    array(
                        'column' => 'l.campaigns',
                        'expr'   => 'gte',
                        'value'  => $entity->getCampaigns()
                    )
                ));

                if (!$isNew) {
                    //get a list of leads that has already had this event applied
                    $leadIds = $repo->getLeadsForEvent($event->getId());
                    if (!empty($leadIds)) {
                        $filter['force'][] = array(
                            'column' => 'l.id',
                            'expr'   => 'notIn',
                            'value'  => implode(',', $leadIds)
                        );
                    }
                }

                //get a list of leads that are before the campaign's date_added and campaign if not already done so
                /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
                $leadModel = $this->factory->getModel('lead');
                $leads     = $leadModel->getEntities(array(
                    'filter' => $filter
                ));

                foreach ($leads as $l) {
                    if ($this->triggerEvent($event, $l, false)) {
                        $log = new LeadEventLog();
                        $log->setIpAddress($ipAddress);
                        $log->setEvent($event);
                        $log->setLead($l);
                        $log->setDateFired(new \DateTime());
                        $event->addLog($log);
                        $persist[] = $event;
                    }
                }
            }

            if (!empty($persist)) {
                $this->getEventRepository()->saveEntities($persist);
            }
        }
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
     */
    public function setEvents(Campaign &$entity, $sessionEvents)
    {
        $order   = 1;
        $existingActions = $entity->getEvents();

        foreach ($sessionEvents as $properties) {
            $isNew = (!empty($properties['id']) && isset($existingActions[$properties['id']])) ? false : true;
            $event = !$isNew ? $existingActions[$properties['id']] : new CampaignEvent();

            foreach ($properties as $f => $v) {
                if (in_array($f, array('id', 'order')))
                    continue;

                $func = "set" .  ucfirst($f);
                if (method_exists($event, $func)) {
                    $event->$func($v);
                }
                $event->setCampaign($entity);
            }
            $event->setOrder($order);
            $order++;
            $entity->addCampaignEvent($properties['id'], $event);
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
            $events = $event->getEvents();
        }
        return $events;
    }


    /**
     * Campaigns a specific event
     *
     * @param CampaignEvent $event
     * @param Lead $lead
     * @param bool $checkApplied
     * @return bool Was event campaigned
     */
    public function triggerEvent(CampaignEvent $event, Lead $lead = null, $checkApplied = true)
    {
        //only campaign events for anonymous users
        if (!$this->security->isAnonymous()) {
            return false;
        }

        if ($lead == null) {
            $leadModel = $this->factory->getModel('lead');
            $lead      = $leadModel->getCurrentLead();
        }

        if ($checkApplied) {
            //get a list of events that has already been performed on this lead
            $appliedEvents = $this->getEventRepository()->getLeadCampaignedEvents($lead->getId());

            //if it's already been done, then skip it
            if (isset($appliedEvents[$event->getId()])) {
                return false;
            }
        }

        //make sure the event still exists
        $campaign  = $event->getCampaign();
        if (!isset($availableEvents[$campaign->getType()])) {
            return false;
        }

        $settings = $availableEvents[$campaign->getType()];
        $args     = array(
            'event'      => array(
                'id'         => $event->getId(),
                'type'       => $event->getType(),
                'name'       => $event->getName(),
                'properties' => $event->getProperties(),
                'campaign'      => array(
                    'id'        => $campaign->getId(),
                    'name'      => $campaign->getName(),
                    'campaigns'    => $campaign->getCampaigns(),
                    'color'     => $campaign->getColor()
                )
            ),
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
            $reflection->invokeArgs($this, $pass);

            return true;
        }

        return false;
    }

    /**
     * Campaign events for the current lead
     *
     * @param Lead $lead
     */
    public function triggerEvents(Lead $lead)
    {
        $campaigns = $lead->getCampaigns();

        //find all published campaigns that is applicable to this campaigns
        /** @var \Mautic\CampaignBundle\Entity\CampaignEventRepository $repo */
        $repo      = $this->getEventRepository();
        $events    = $repo->getPublishedByCampaignTotal($campaigns);
        /** @var \Mautic\CoreBundle\Entity\IpAddress $ipAddress */
        $ipAddress = $this->factory->getIpAddress();
        $persist   = array();
        if (!empty($events)) {
            //get a list of actions that has already been applied to this lead
            $appliedEvents = $repo->getLeadCampaignedEvents($lead->getId());

            foreach ($events as $event) {
                if (isset($appliedEvents[$event->getId()])) {
                    //don't apply the event to the lead if it's already been done
                    continue;
                }

                if ($this->triggerEvent($event, $lead, false)) {
                    $log = new LeadEventLog();
                    $log->setIpAddress($ipAddress);
                    $log->setEvent($event);
                    $log->setLead($lead);
                    $log->setDateFired(new \DateTime());

                    $event->addLog($log);
                    $persist[] = $event;
                }
            }
        }

        if (!empty($persist)) {
            $this->getEventRepository()->saveEntities($persist);
        }
    }

    /**
     * Returns configured color based on passed in $campaigns
     *
     * @param $campaigns
     *
     * @return string
     */
    public function getColorForLeadCampaigns($campaigns)
    {
        static $campaigns = array();

        if (empty($campaigns)) {
            $campaigns = $this->getRepository()->getCampaignColors();
        }

        foreach ($campaigns as $campaign) {
            if ($campaigns >= $campaign['campaigns']) {
                return $campaign['color'];
            }
        }

        return '';
    }
}