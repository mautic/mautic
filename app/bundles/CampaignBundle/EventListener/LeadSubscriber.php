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
    static public function getSubscribedEvents ()
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
    public function onLeadListChange (ListChangeEvent $event)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model     = $this->factory->getModel('campaign');
        $leadModel = $this->factory->getModel('lead');
        $lead      = $event->getLead();
        $list      = $event->getList();
        $action    = $event->wasAdded() ? 'added' : 'removed';

        //get campaigns for the list
        $listCampaigns = $model->getRepository()->getPublishedCampaignsByLeadLists(array($list->getId()));

        $leadLists   = $leadModel->getLists($lead);
        $leadListIds = array_keys($leadLists);

        if (!empty($listCampaigns)) {
            foreach ($listCampaigns as $c) {
                if ($action == 'added') {
                    $model->addLead($c, $lead);
                } else {
                    $lists           = $c->getLists();
                    $campaignListIds = array_keys($lists->toArray());

                    if (array_intersect($leadListIds, $campaignListIds)) {
                        break;
                    }

                    $model->removeLead($c, $lead);
                }
            }
        }
    }
}