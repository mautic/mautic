<?php

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
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
     * @var array
     */
    private $campaignLists;

    /**
     * @var array
     */
    private $campaignReferences;

    /**
     * @var MembershipManager
     */
    private $membershipManager;

    /**
     * @var EventCollector
     */
    private $eventCollector;

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

    public function __construct(
        MembershipManager $membershipManager,
        EventCollector $eventCollector,
        CampaignModel $campaignModel,
        LeadModel $leadModel,
        TranslatorInterface $translator,
        EntityManager $entityManager,
        RouterInterface $router
    ) {
        $this->membershipManager         = $membershipManager;
        $this->eventCollector            = $eventCollector;
        $this->campaignModel             = $campaignModel;
        $this->leadModel                 = $leadModel;
        $this->translator                = $translator;
        $this->entityManager             = $entityManager;
        $this->router                    = $router;
        $this->segmentRepository         = $entityManager->getRepository(LeadList::class);
        $this->contactEventLogRepository = $entityManager->getRepository(LeadEventLog::class);
        $this->contactRepository         = $entityManager->getRepository(CampaignLead::class);
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
     */
    public function onLeadListBatchChange(ListChangeEvent $event)
    {
        $leads         = $event->getLeads();
        $list          = $event->getList();
        $action        = $event->wasAdded() ? 'added' : 'removed';
        $listCampaigns = [];

        //get campaigns for the list
        $listCampaigns[$list->getId()] = $this->campaignModel->getRepository()->getPublishedCampaignsByLeadLists($list->getId());

        $leadLists = $this->segmentRepository->getLeadLists($leads, true, true);

        if (!empty($listCampaigns[$list->getId()])) {
            $contactCollection = new ArrayCollection();
            foreach ($leads as $lead) {
                $id['id'] = $lead['id'] ?? $lead; // getReference needs this to be [identifier => value]
                $contactCollection->set($id['id'], $this->entityManager->getReference(Lead::class, $id));
            }

            foreach ($listCampaigns[$list->getId()] as $c) {
                if (!isset($this->campaignReferences[$c['id']])) {
                    $this->campaignReferences[$c['id']] = $this->entityManager->getReference(Campaign::class, $c['id']);
                }

                if ('added' == $action) {
                    $this->membershipManager->addContacts($contactCollection, $this->campaignReferences[$c['id']], false);
                } else {
                    if (!isset($this->campaignLists[$c['id']])) {
                        $this->campaignLists[$c['id']] = [];
                        foreach ($c['lists'] as $l) {
                            $this->campaignLists[$c['id']][] = $l['id'];
                        }
                    }

                    $removeContacts = new ArrayCollection();
                    foreach ($contactCollection as $id => $contact) {
                        $lists = $leadLists[$id] ?? [];
                        if (array_intersect(array_keys($lists), $this->campaignLists[$c['id']])) {
                            continue;
                        } else {
                            $removeContacts->set($id, $contact);
                        }
                    }

                    $this->membershipManager->removeContacts($removeContacts, $this->campaignReferences[$c['id']], true);
                }
            }
            $this->entityManager->clear(Lead::class);
        }

        // Save memory with batch processing
        unset($event, $leads, $list, $listCampaigns, $leadLists);
    }

    /**
     * Add/remove leads from campaigns based on lead list changes.
     */
    public function onLeadListChange(ListChangeEvent $event)
    {
        $lead   = $event->getLead();
        $list   = $event->getList();
        $action = $event->wasAdded() ? 'added' : 'removed';

        //get campaigns for the list
        $listCampaigns = $this->campaignModel->getRepository()->getPublishedCampaignsByLeadLists($list->getId());

        $leadLists     = $this->leadModel->getLists($lead, true);
        $leadListIds   = array_keys($leadLists);
        $campaignLists = [];

        // If the lead was removed then don't count it
        if ('removed' == $action) {
            $key = array_search($list->getId(), $leadListIds);
            unset($leadListIds[$key]);
        }

        if (!empty($listCampaigns)) {
            foreach ($listCampaigns as $c) {
                /** @var Campaign $campaign */
                $campaign = $this->entityManager->getReference(Campaign::class, $c['id']);

                if (!isset($campaignLists[$c['id']])) {
                    $campaignLists[$c['id']] = array_keys($c['lists']);
                }

                if ('added' == $action) {
                    $this->membershipManager->addContact($lead, $campaign, false);
                } else {
                    if (array_intersect($leadListIds, $campaignLists[$c['id']])) {
                        continue;
                    }

                    $this->membershipManager->removeContact($lead, $campaign, true);
                }

                unset($campaign);
            }
        }
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addTimelineEvents($event, 'campaign.event', $this->translator->trans('mautic.campaign.triggered'));
        $this->addTimelineEvents($event, 'campaign.event.scheduled', $this->translator->trans('mautic.campaign.scheduled'));
    }

    /**
     * Update records after lead merge.
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->contactEventLogRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
        $this->contactRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }

    /**
     * @param $eventTypeKey
     * @param $eventTypeName
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
        $logs                      = $this->contactEventLogRepository->getLeadLogs($event->getLeadId(), $options);
        $eventSettings             = $this->eventCollector->getEventsArray();

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
