<?php

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventCollector $eventCollector,
        private TranslatorInterface $translator,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $this->addTimelineEvents($event, 'campaign.event', $this->translator->trans('mautic.campaign.triggered'));
        $this->addTimelineEvents($event, 'campaign.event.scheduled', $this->translator->trans('mautic.campaign.scheduled'));
    }

    /**
     * Update records after lead merge.
     */
    public function onLeadMerge(LeadMergeEvent $event): void
    {
        /** @var LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->entityManager->getRepository(LeadEventLog::class);

        /** @var LeadRepository $campaignLeadRepository */
        $campaignLeadRepository = $this->entityManager->getRepository(CampaignLead::class);

        $leadEventLogRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
        $campaignLeadRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }

    /**
     * @param string $eventTypeKey
     * @param string $eventTypeName
     */
    private function addTimelineEvents(LeadTimelineEvent $event, $eventTypeKey, $eventTypeName): void
    {
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('campaignList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        /** @var LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->entityManager->getRepository(LeadEventLog::class);

        $options                   = $event->getQueryOptions();
        $options['scheduledState'] = ('campaign.event' === $eventTypeKey) ? false : true;
        $logs                      = $leadEventLogRepository->getLeadLogs($event->getLeadId(), $options);
        $eventSettings             = $this->eventCollector->getEventsArray();

        // Add total number to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            foreach ($logs['results'] as $log) {
                $template = (!empty($eventSettings['action'][$log['type']]['timelineTemplate']))
                    ? $eventSettings['action'][$log['type']]['timelineTemplate'] : '@MauticCampaign/SubscribedEvents/Timeline/index.html.twig';

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
