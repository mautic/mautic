<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\CampaignBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            LeadEvents::LEAD_LIST_CHANGE => array('onLeadListChange', 0)
        );
    }


    /**
     * Add/remove leads from campaigns based on lead list changes
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
        $em     = $this->factory->getEntityManager();

        //get campaigns for the list
        $listCampaigns = $model->getRepository()->getPublishedCampaignsByLeadLists(array($list->getId()), true);

        if (!empty($listCampaigns)) {
            foreach ($listCampaigns as $c) {
                if ($action == 'added') {
                    $model->addLead($em->getReference('MauticCampaignBundle:Campaign', $c['id']), $lead);
                } else {
                    $model->removeLead($em->getReference('MauticCampaignBundle:Campaign', $c['id']), $lead);
                }
            }
        }
    }
}