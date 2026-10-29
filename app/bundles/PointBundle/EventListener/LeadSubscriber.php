<?php

namespace Mautic\PointBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\LeadBundle\Entity\PointsChangeLogRepository;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadPostMergeEvent;
use Mautic\LeadBundle\Event\LeadPostSaveEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\PointBundle\Entity\LeadPointLogRepository;
use Mautic\PointBundle\Entity\LeadTriggerLogRepository;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class LeadSubscriber
{
    public function __construct(
        private TriggerModel $triggerModel,
        private TranslatorInterface $translator,
        private PointsChangeLogRepository $pointsChangeLogRepository,
        private LeadPointLogRepository $leadPointLogRepository,
        private LeadTriggerLogRepository $leadTriggerLogRepository,
    ) {
    }

    /**
     * Trigger applicable events for the lead.
     */
    #[AsEventListener(event: PointsChangeEvent::class)]
    public function onLeadPointsChange(PointsChangeEvent $event): void
    {
        $this->triggerModel->triggerEvents($event->getLead());
    }

    /**
     * Handle point triggers for new leads (including 0 point triggers).
     */
    #[AsEventListener(event: LeadPostSaveEvent::class, priority: -1)]
    public function onLeadSave(LeadEvent $event): void
    {
        if ($event->isNew()) {
            $this->triggerModel->triggerEvents($event->getLead());
        }
    }

    /**
     * Compile events for the lead timeline.
     */
    #[AsEventListener(event: LeadTimelineEvent::class)]
    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        // Set available event types
        $eventTypeKey  = 'point.gained';
        $eventTypeName = $this->translator->trans('mautic.point.event.gained');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('pointList');

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $logs = $this->pointsChangeLogRepository->getLeadTimelineEvents($event->getLeadId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($logs['results'] as $log) {
                $eventLabel = $log['eventName'].' / '.$log['delta'];
                if (!empty($log['groupName'])) {
                    $eventLabel .= ' ('.$log['groupName'].')';
                }

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$log['id'],
                        'eventLabel' => $eventLabel,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $log['dateAdded'],
                        'extra'      => [
                            'log' => $log,
                        ],
                        'icon'      => 'ri-calculator-line',
                        'contactId' => $log['lead_id'],
                    ]
                );
            }
        }
    }

    #[AsEventListener(event: LeadPostMergeEvent::class)]
    public function onLeadMerge(LeadMergeEvent $event): void
    {
        $this->leadPointLogRepository->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $this->leadTriggerLogRepository->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}
