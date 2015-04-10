<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0)
        );
    }

    /**
     * Add event triggers and actions
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        //Add actions
        $action = array(
            'label'       => 'mautic.lead.lead.events.changepoints',
            'description' => 'mautic.lead.lead.events.changepoints_descr',
            'formType'    => 'leadpoints_action',
            'callback'    => '\Mautic\LeadBundle\Helper\CampaignEventHelper::changePoints'
        );
        $event->addAction('lead.changepoints', $action);

        $action = array(
            'label'        => 'mautic.lead.lead.events.changelist',
            'description'  => 'mautic.lead.lead.events.changelist_descr',
            'formType'     => 'leadlist_action',
            'callback'     => '\Mautic\LeadBundle\Helper\CampaignEventHelper::changeLists'
        );
        $event->addAction('lead.changelist', $action);

        $action = array(
            'label'        => 'mautic.lead.lead.events.updatelead',
            'description'  => 'mautic.lead.lead.events.updatelead_descr',
            'formType'     => 'updatelead_action',
            'formTheme'    => 'MauticLeadBundle:FormTheme\ActionUpdateLead',
            'callback'     => '\Mautic\LeadBundle\Helper\CampaignEventHelper::updateLead'
        );
        $event->addAction('lead.updatelead', $action);
    }
}
