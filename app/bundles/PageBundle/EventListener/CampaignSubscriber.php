<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;

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
    static public function getSubscribedEvents ()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0),
            PageEvents::PAGE_ON_HIT           => array('onPageHit', 0)
        );
    }

    /**
     * Add event triggers and actions
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild (CampaignBuilderEvent $event)
    {
        //Add trigger
        $pageHitTrigger = array(
            'group'       => 'mautic.page.event.action.group',
            'label'       => 'mautic.page.campaign.trigger.pagehit',
            'description' => 'mautic.page.campaign.trigger.pagehit_descr',
            'formType'    => 'campaigntrigger_pagehit',
            'callback'    => '\Mautic\PageBundle\Helper\CampaignEventHelper::onPageHit'
        );
        $event->addTrigger('page.pagehit', $pageHitTrigger);
    }

    /**
     * Trigger actions for page hits
     *
     * @param PageHitEvent $event
     */
    public function onPageHit (PageHitEvent $event)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model  = $this->factory->getModel('campaign');
        $hit    = $event->getHit();
        $page   = $hit->getPage();
        $typeId = $page instanceof Page ? 'page.pagehit.' . $page->getId() : null;
        $model->triggerEvent('page.pagehit', $hit, $typeId);
    }
}