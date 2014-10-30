<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class CampaignSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class CampaignSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0),
            LeadEvents::LEAD_POST_SAVE        => array('onLeadPostSave', 0),
            LeadEvents::LEAD_POINTS_CHANGE    => array('onLeadPointChange', 0),
            LeadEvents::LEAD_LIST_CHANGE    => array('onLeadListChange', 0)
        );
    }

    /**
     * Add event triggers and actions
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        //Add triggers
        $trigger = array(
            'label'        => 'mautic.lead.lead.events.leadcreated',
            'description'  => 'mautic.lead.lead.events.leadcreated_descr'
        );
        $event->addSystemAction('lead.created', $trigger);

        $trigger = array(
            'label'        => 'mautic.lead.lead.events.pointchange',
            'description'  => 'mautic.lead.lead.events.pointchange_descr',
            'formType'     => 'leadpoints_trigger',
            'callback'     => '\Mautic\LeadBundle\Helper\CampaignEventHelper::validatePointChange'
        );
        $event->addSystemAction('lead.pointchange', $trigger);

        $trigger = array(
            'label'        => 'mautic.lead.lead.events.listchange',
            'description'  => 'mautic.lead.lead.events.listchange_descr',
            'formType'     => 'leadlist_trigger',
            'callback'     => '\Mautic\LeadBundle\Helper\CampaignEventHelper::validateListChange'
        );
        $event->addSystemAction('lead.listchange', $trigger);

        //Add actions
        $action = array(
            'label'       => 'mautic.lead.lead.events.changepoints',
            'description' => 'mautic.lead.lead.events.changepoints_descr',
            'formType'    => 'leadpoints_action',
            'callback'    => '\Mautic\LeadBundle\Helper\CampaignEventHelper::changePoints'
        );
        $event->addOutcome('lead.changepoints', $action);

        $action = array(
            'label'        => 'mautic.lead.lead.events.changelist',
            'description'  => 'mautic.lead.lead.events.changelist_descr',
            'formType'     => 'leadlist_action',
            'callback'     => '\Mautic\LeadBundle\Helper\CampaignEventHelper::changeLists'
        );
        $event->addOutcome('lead.changelist', $action);
    }

    /**
     * Trigger new lead campaign events
     *
     * @param LeadEvent $event
     */
    public function onLeadPostSave(LeadEvent $event)
    {
        if ($event->isNew()) {
            /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
            $model = $this->factory->getModel('campaign');
            $model->triggerEvent('lead.created', $event->getLead());
        }
    }

    /**
     * Trigger lead point change campaign events
     *
     * @param PointsChangeEvent $event
     */
    public function onLeadPointChange(PointsChangeEvent $event)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model = $this->factory->getModel('campaign');
        $lead  = $event->getLead();
        $name  = 'lead.pointchange.'.$lead->getId() . '.' . $event->getOldPoints() . '.' . $event->getNewPoints();
        $model->triggerEvent('lead.pointchange', $event, $name);
    }

    /**
     * Trigger lead list change campaign events
     *
     * @param ListChangeEvent $event
     */
    public function onLeadListChange(ListChangeEvent $event)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model  = $this->factory->getModel('campaign');
        $lead   = $event->getLead();
        $list   = $event->getList();
        $action = $event->wasAdded() ? 'added' : 'removed';
        $name   = 'lead.listchange.' . $lead->getId() . '.' . $list->getId() . '.' . $action;
        $model->triggerEvent('lead.listchange', $event, $name);
    }
}
