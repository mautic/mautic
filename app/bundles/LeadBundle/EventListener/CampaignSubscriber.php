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
        );
    }

    /**
     * Add a lead list change action to available event triggers
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        //add lead generation submit action
        $leadEvent = array(
            'group'        => 'mautic.lead.lead.events.group',
            'label'        => 'mautic.lead.lead.events.changelist',
            'description'  => 'mautic.lead.lead.events.changelist',
            'formType'     => 'lead_event_leadlist',
            'callback'     => '\Mautic\LeadBundle\Helper\CampaignEventHelper::changeList'
        );
        $event->addEvent('lead.changelist', $leadEvent);
    }
}
