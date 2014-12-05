<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;

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
            LeadEvents::LEAD_LIST_CHANGE => array('onLeadListChange', 0),
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0)
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

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey = 'campaign.evented';
        $eventTypeName = $this->translator->trans('mautic.campaign.triggered');
        $event->addEventType($eventTypeKey, $eventTypeName);

        // Decide if those events are filtered
        $filter = $event->getEventFilter();
        $loadAllEvents = !isset($filter[0]);
        $eventFilterExists = in_array($eventTypeKey, $filter);

        if (!$loadAllEvents && !$eventFilterExists) {
            return;
        }

        $lead    = $event->getLead();
        $options = array('ipIds' => array(), 'filters' => $filter);

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        /*
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }
        */

        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $logRepository */
        $logRepository = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');

        $logs = $logRepository->getLeadLogs($lead->getId(), $options);

        // Add the hits to the event array
        foreach ($logs as $log) {
            $event->addEvent(array(
                'event'     => $eventTypeKey,
                'eventLabel' => $eventTypeName,
                'timestamp' => $log['dateTriggered'],
                'extra'     => array(
                    'log' => $log
                ),
                'contentTemplate' => 'MauticCampaignBundle:SubscribedEvents\Timeline:index.html.php'
            ));
        }
    }
}