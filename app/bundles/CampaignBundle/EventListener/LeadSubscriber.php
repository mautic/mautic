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
use Mautic\LeadBundle\Event\LeadMergeEvent;
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
            LeadEvents::LEAD_LIST_BATCH_CHANGE  => array('onLeadListBatchChange', 0),
            LeadEvents::LEAD_LIST_CHANGE        => array('onLeadListChange', 0),
            LeadEvents::TIMELINE_ON_GENERATE    => array('onTimelineGenerate', 0),
            LeadEvents::LEAD_POST_MERGE         => array('onLeadMerge', 0)
        );
    }

    /**
     * Add/remove leads from campaigns based on batch lead list changes
     *
     * @param ListChangeEvent $event
     */
    public function onLeadListBatchChange (ListChangeEvent $event)
    {
        static $campaignLists = array(), $listCampaigns = array(), $campaignReferences = array();

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model     = $this->factory->getModel('campaign');
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        $leads     = $event->getLeads();
        $list      = $event->getList();
        $action    = $event->wasAdded() ? 'added' : 'removed';
        $em        = $this->factory->getEntityManager();

        //get campaigns for the list
        if (!isset($listCampaigns[$list->getId()])) {
            $listCampaigns[$list->getId()] = $model->getRepository()->getPublishedCampaignsByLeadLists(array($list->getId()), true);
        }

        $leadLists = $em->getRepository('MauticLeadBundle:LeadList')->getLeadLists($leads, true, true);

        if (!empty($listCampaigns[$list->getId()])) {
            foreach ($listCampaigns[$list->getId()] as $c) {
                if (!isset($campaignReferences[$c['id']])) {
                    $campaignReferences[$c['id']] = $em->getReference('MauticCampaignBundle:Campaign', $c['id']);
                }

                if ($action == 'added') {
                    $model->addLeads($campaignReferences[$c['id']], $leads, false, true);
                } else {
                    if (!isset($campaignLists[$c['id']])) {
                        $campaignLists[$c['id']] = array();
                        foreach ($c['lists'] as $l) {
                            $campaignLists[$c['id']][] = $l['id'];
                        }
                    }

                    $removeLeads = array();
                    foreach ($leads as $l) {
                        $lists = (isset($leadLists[$l])) ? $leadLists[$l] : array();
                        if (array_intersect($lists, $campaignLists[$c['id']])) {
                            continue;
                        } else {
                            $removeLeads[] = $l;
                        }
                    }

                    $model->removeLeads($campaignReferences[$c['id']], $removeLeads, false, true);
                }
            }
        }

        // Save memory with batch processing
        unset($event, $em, $model, $leadModel, $leads, $list, $listCampaigns, $leadLists);
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
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');
        $lead      = $event->getLead();
        $list      = $event->getList();
        $action    = $event->wasAdded() ? 'added' : 'removed';
        $repo      = $model->getRepository();
        $em        = $this->factory->getEntityManager();

        //get campaigns for the list
        $listCampaigns = $repo->getPublishedCampaignsByLeadLists(array($list->getId()), true);

        $leadLists   = $leadModel->getLists($lead, true);
        $leadListIds = array_keys($leadLists);

        // If the lead was removed then don't count it
        if ($action == 'removed') {
            $key = array_search($list->getId(), $leadListIds);
            unset($leadListIds[$key]);
        }

        if (!empty($listCampaigns)) {
            foreach ($listCampaigns as $c) {
                $campaign = $em->getReference('MauticCampaignBundle:Campaign', $c['id']);

                if (!isset($campaignLists[$c['id']])) {
                    $campaignLists[$c['id']] = array_keys($c['lists']);
                }

                if ($action == 'added') {
                    $model->addLead($campaign, $lead);
                } else {
                    if (array_intersect($leadListIds, $campaignLists[$c['id']])) {
                        continue;
                    }

                    $model->removeLead($campaign, $lead);
                }

                unset($campaign);
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
        $eventTypeKey  = 'campaign.evented';
        $eventTypeName = $this->translator->trans('mautic.campaign.triggered');
        $event->addEventType($eventTypeKey, $eventTypeName);

        // Decide if those events are filtered
        $filters = $event->getEventFilters();

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $lead    = $event->getLead();
        $options = array('ipIds' => array(), 'filters' => $filters);

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        /*
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }
        */

        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $logRepository */
        $logRepository = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');

        $logs = $logRepository->getLeadLogs($lead->getId(), $options);

        $model         = $this->factory->getModel('campaign');
        $eventSettings = $model->getEvents();

        // Add the hits to the event array
        foreach ($logs as $log) {
            if (!is_array($log['metadata'])) {
                $log['metadata'] = ($log['metadata'] !== null) ? unserialize($log['metadata']) : array();
            }
            $template        = (!empty($eventSettings['action'][$log['type']]['timelineTemplate']))
                ? $eventSettings['action'][$log['type']]['timelineTemplate'] : 'MauticCampaignBundle:SubscribedEvents\Timeline:index.html.php';

            $event->addEvent(
                array(
                    'event'           => $eventTypeKey,
                    'eventLabel'      => $eventTypeName,
                    'timestamp'       => $log['dateTriggered'],
                    'extra'           => array(
                        'log' => $log
                    ),
                    'contentTemplate' => $template
                )
            );
        }
    }

    /**
     * Update records after lead merge
     *
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog')->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());

        $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:Lead')->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }
}