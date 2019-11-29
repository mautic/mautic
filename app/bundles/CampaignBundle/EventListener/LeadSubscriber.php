<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var LeadListRepository
     */
    private $segmentRepository;

    /**
     * @var LeadEventLogRepository
     */
    private $contactEventLogRepository;

    /**
     * @var LeadRepository
     */
    private $contactRepository;

    /**
     * @param CampaignModel       $campaignModel
     * @param LeadModel           $leadModel
     * @param TranslatorInterface $translator
     * @param EntityManager       $entityManager
     * @param RouterInterface     $router
     * @param CorePermissions     $security
     */
    public function __construct(
        CampaignModel $campaignModel,
        LeadModel $leadModel,
        TranslatorInterface $translator,
        EntityManager $entityManager,
        RouterInterface $router,
        CorePermissions $security
    ) {
        $this->campaignModel             = $campaignModel;
        $this->leadModel                 = $leadModel;
        $this->translator                = $translator;
        $this->entityManager             = $entityManager;
        $this->router                    = $router;
        $this->security                  = $security;
        $this->campaignRepository        = $entityManager->getRepository(Campaign::class);
        $this->segmentRepository         = $entityManager->getRepository(LeadList::class);
        $this->contactEventLogRepository = $entityManager->getRepository(LeadEventLog::class);
        $this->contactRepository         = $entityManager->getRepository(Lead::class);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_LIST_BATCH_CHANGE => ['onLeadListBatchChange', 0],
            LeadEvents::LEAD_LIST_CHANGE       => ['onLeadListChange', 0],
            LeadEvents::TIMELINE_ON_GENERATE   => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE        => ['onLeadMerge', 0],
        ];
    }

    /**
     * Add/remove leads from campaigns based on batch lead list changes.
     *
     * @param ListChangeEvent $event
     */
    public function onLeadListBatchChange(ListChangeEvent $event)
    {
        static $campaignLists = [], $listCampaigns = [], $campaignReferences = [];

        $leads  = $event->getLeads();
        $list   = $event->getList();
        $action = $event->wasAdded() ? 'added' : 'removed';

        //get campaigns for the list
        if (!isset($listCampaigns[$list->getId()])) {
            $listCampaigns[$list->getId()] = $this->campaignRepository->getPublishedCampaignsByLeadLists($list->getId(), $this->security->isGranted('campaign:campaigns:viewother'));
        }

        $leadLists = $this->segmentRepository->getLeadLists($leads, true, true);

        if (!empty($listCampaigns[$list->getId()])) {
            foreach ($listCampaigns[$list->getId()] as $c) {
                if (!isset($campaignReferences[$c['id']])) {
                    $campaignReferences[$c['id']] = $this->entityManager->getReference(Campaign::class, $c['id']);
                }

                if ($action == 'added') {
                    $this->campaignModel->addLeads($campaignReferences[$c['id']], $leads, false, true);
                } else {
                    if (!isset($campaignLists[$c['id']])) {
                        $campaignLists[$c['id']] = [];
                        foreach ($c['lists'] as $l) {
                            $campaignLists[$c['id']][] = $l['id'];
                        }
                    }

                    $removeLeads = [];
                    foreach ($leads as $l) {
                        $lists = (isset($leadLists[$l['id']])) ? $leadLists[$l['id']] : [];
                        if (array_intersect(array_keys($lists), $campaignLists[$c['id']])) {
                            continue;
                        } else {
                            $removeLeads[] = $l;
                        }
                    }

                    $this->campaignModel->removeLeads($campaignReferences[$c['id']], $removeLeads, false, true);
                }
            }
        }

        // Save memory with batch processing
        unset($event, $model, $leads, $list, $listCampaigns, $leadLists);
    }

    /**
     * Add/remove leads from campaigns based on lead list changes.
     *
     * @param ListChangeEvent $event
     */
    public function onLeadListChange(ListChangeEvent $event)
    {
        $lead   = $event->getLead();
        $list   = $event->getList();
        $action = $event->wasAdded() ? 'added' : 'removed';

        //get campaigns for the list
        $listCampaigns = $this->campaignRepository->getPublishedCampaignsByLeadLists($list->getId(), $this->security->isGranted('campaign:campaigns:viewother'));

        $leadLists     = $this->leadModel->getLists($lead, true);
        $leadListIds   = array_keys($leadLists);
        $campaignLists = [];

        // If the lead was removed then don't count it
        if ($action == 'removed') {
            $key = array_search($list->getId(), $leadListIds);
            unset($leadListIds[$key]);
        }

        if (!empty($listCampaigns)) {
            foreach ($listCampaigns as $c) {
                $campaign = $this->entityManager->getReference(Campaign::class, $c['id']);

                if (!isset($campaignLists[$c['id']])) {
                    $campaignLists[$c['id']] = array_keys($c['lists']);
                }

                if ($action == 'added') {
                    $this->campaignModel->addLead($campaign, $lead);
                } else {
                    if (array_intersect($leadListIds, $campaignLists[$c['id']])) {
                        continue;
                    }

                    $this->campaignModel->removeLead($campaign, $lead);
                }

                unset($campaign);
            }
        }
    }

    /**
     * Compile events for the lead timeline.
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addTimelineEvents($event, 'campaign.event', $this->translator->trans('mautic.campaign.triggered'));
        $this->addTimelineEvents($event, 'campaign.event.scheduled', $this->translator->trans('mautic.campaign.scheduled'));
    }

    /**
     * Update records after lead merge.
     *
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->contactEventLogRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
        $this->contactRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }

    /**
     * @param LeadTimelineEvent $event
     * @param                   $eventTypeKey
     * @param                   $eventTypeName
     */
    private function addTimelineEvents(LeadTimelineEvent $event, $eventTypeKey, $eventTypeName)
    {
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('campaignList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $options                   = $event->getQueryOptions();
        $options['scheduledState'] = ('campaign.event' === $eventTypeKey) ? false : true;
        $logs                      = $this->LeadEventLogRepository->getLeadLogs($event->getLeadId(), $options);
        $eventSettings             = $this->campaignModel->getEvents();

        // Add total number to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            foreach ($logs['results'] as $log) {
                $template = (!empty($eventSettings['action'][$log['type']]['timelineTemplate']))
                    ? $eventSettings['action'][$log['type']]['timelineTemplate'] : 'MauticCampaignBundle:SubscribedEvents\Timeline:index.html.php';

                $label = $log['event_name'].' / '.$log['campaign_name'];

                if (empty($log['isScheduled']) && empty($log['dateTriggered'])) {
                    // Note as cancelled
                    $label .= ' <i data-toggle="tooltip" title="'.$this->translator->trans('mautic.campaign.event.cancelled')
                        .'" class="fa fa-calendar-times-o text-warning timeline-campaign-event-cancelled-'.$log['event_id'].'"></i>';
                }

                if ((!empty($log['metadata']['errors']) && empty($log['dateTriggered'])) || !empty($log['metadata']['failed']) || !empty($log['fail_reason'])) {
                    $label .= ' <i data-toggle="tooltip" title="'.$this->translator->trans('mautic.campaign.event.has_last_attempt_error')
                        .'" class="fa fa-warning text-danger"></i>';
                }

                $extra = [
                    'log' => $log,
                ];

                if ($event->isForTimeline()) {
                    $extra['campaignEventSettings'] = $eventSettings;
                }

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$log['log_id'],
                        'eventLabel' => [
                            'label' => $label,
                            'href'  => $this->router->generate(
                                'mautic_campaign_action',
                                ['objectAction' => 'view', 'objectId' => $log['campaign_id']]
                            ),
                        ],
                        'eventType'       => $eventTypeName,
                        'timestamp'       => $log['dateTriggered'],
                        'extra'           => $extra,
                        'contentTemplate' => $template,
                        'icon'            => 'fa-clock-o',
                        'contactId'       => $log['lead_id'],
                    ]
                );
            }
        }
    }
}
