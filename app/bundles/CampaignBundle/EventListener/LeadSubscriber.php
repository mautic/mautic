<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventCollector $eventCollector,
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private EventRepository $eventRepository,
        private LeadEventLogRepository $leadEventLogRepository,
        private LeadRepository $campaignLeadRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadTimelineEvent::class => ['onTimelineGenerate', 0],
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
        $this->leadEventLogRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
        $this->campaignLeadRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }

    private function addTimelineEvents(LeadTimelineEvent $event, string $eventTypeKey, string $eventTypeName): void
    {
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('campaignList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $options                   = $event->getQueryOptions();
        $options['scheduledState'] = 'campaign.event' !== $eventTypeKey;
        $logs                      = $this->leadEventLogRepository->getLeadLogs($event->getLeadId(), $options);
        $eventSettings             = $this->eventCollector->getEventsArray();

        // Add total number to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            foreach ($logs['results'] as $log) {
                $template = (!empty($eventSettings['action'][$log['type']]['timelineTemplate']))
                    ? $eventSettings['action'][$log['type']]['timelineTemplate'] : '@MauticCampaign/SubscribedEvents/Timeline/index.html.twig';

                $label = $log['event_name'].' / '.$log['campaign_name'];

                // Case 1: This event was executed as a redirect because original event was deleted
                // Show "Rescheduled from" message when:
                // - Check metadata for rescheduled event information
                if (!empty($log['metadata'])
                    && is_array($log['metadata'])
                    && !empty($log['metadata']['redirect_applied'])
                    && !empty($log['metadata']['originalEventName'])) {
                    $label = $log['event_name'].' / '.$log['campaign_name'].
                        ' <span class="small">'.$this->translator->trans('mautic.campaign.event.redirected',
                            ['%original%' => $log['metadata']['originalEventName']]).'</span>';
                }

                // Case 2: Event executed before being deleted - show "Deleted" label
                // Show only if:
                // - Event is marked as deleted
                // - Event has been triggered (not just scheduled)
                if (!empty($log['event_deleted_timestamp'])) {
                    $label .= ' <span class="label label-danger">'.$this->translator->trans('mautic.campaign.deleted').
                        '</span>';
                }

                // Case 3: Event scheduled to execute deleted event - don't show any special message
                // (default display with no additional labels)

                if (empty($log['isScheduled']) && empty($log['dateTriggered'])) {
                    // Note as cancelled
                    $label .= ' <i data-toggle="tooltip" title="'.$this->translator->trans('mautic.campaign.event.cancelled')
                        .'" class="ri-calendar-close-fill text-warning timeline-campaign-event-cancelled-'.$log['event_id'].'"></i>';
                }

                if ((!empty($log['metadata']['errors']) && empty($log['dateTriggered'])) || !empty($log['metadata']['failed']) || !empty($log['fail_reason'])) {
                    $label .= ' <i data-toggle="tooltip" title="'.$this->translator->trans('mautic.campaign.event.has_last_attempt_error')
                        .'" class="ri-alert-line text-danger"></i>';
                }

                $extra = [
                    'log' => $log,
                ];

                // check if the event is a condition or decision event
                $eventEntity = $this->getConditionOrDecisionEvent($log['event_id']);
                if ($eventEntity) {
                    $extra['eventDetails'] = $this->getCampaignEventDetails($log);

                    $toolTipClass = 'yes' === $extra['eventDetails']['path'] ? 'text-success' : 'text-danger';
                    $toolTip      = $this->translator->trans('mautic.campaign.event.path.tooltip', ['%path%' => ucfirst($extra['eventDetails']['path'])]);

                    $label .= sprintf(' <i class="ri-node-tree %s" data-toggle="tooltip" title="%s"></i>', $toolTipClass, $toolTip);
                }

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
                        'icon'            => 'ri-time-line',
                        'contactId'       => $log['lead_id'],
                    ]
                );
            }
        }
    }

    /**
     * Check if the given campaign event is a condition or decision event.
     */
    private function getConditionOrDecisionEvent(int $id): ?Event
    {
        $entities = $this->eventRepository->findBy([
            'id'        => $id,
            'eventType' => [Event::TYPE_CONDITION, Event::TYPE_DECISION],
        ]);

        return $entities[0] ?? null;
    }

    /**
     * Get details for the campaign event.
     *
     * @param array<string, mixed> $log
     *
     * @return array<string, mixed>
     */
    private function getCampaignEventDetails(array $log): array
    {
        $metadata = $log['metadata'] ?? [];
        if (isset($metadata['operator'])) {
            $operators            = OperatorOptions::getFilterExpressionFunctions();
            $label                = $operators[$metadata['operator']]['label'] ?? $metadata['operator'];
            $metadata['operator'] = $this->translator->trans($label);
        }

        // write the actual value to the properties that was used to compare the field value with.
        return [
            'path'       => '1' === $log['nonActionPathTaken'] ? 'no' : 'yes',
            'properties' => $metadata,
        ];
    }
}
