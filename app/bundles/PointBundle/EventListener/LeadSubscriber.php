<?php

namespace Mautic\PointBundle\EventListener;

use Mautic\LeadBundle\Entity\PointsChangeLogRepository;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PointBundle\Entity\LeadPointLogRepository;
use Mautic\PointBundle\Entity\LeadTriggerLogRepository;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * @var TriggerModel
     */
    private $triggerModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PointsChangeLogRepository
     */
    private $pointsChangeLogRepository;

    /**
     * @var LeadPointLogRepository
     */
    private $leadPointLogRepository;

    /**
     * @var LeadTriggerLogRepository
     */
    private $leadTriggerLogRepository;

    public function __construct(
        TriggerModel $triggerModel,
        TranslatorInterface $translator,
        PointsChangeLogRepository $pointsChangeLogRepository,
        LeadPointLogRepository $leadPointLogRepository,
        LeadTriggerLogRepository $leadTriggerLogRepository
    ) {
        $this->triggerModel              = $triggerModel;
        $this->translator                = $translator;
        $this->pointsChangeLogRepository = $pointsChangeLogRepository;
        $this->leadPointLogRepository    = $leadPointLogRepository;
        $this->leadTriggerLogRepository  = $leadTriggerLogRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POINTS_CHANGE   => ['onLeadPointsChange', 0],
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
            LeadEvents::LEAD_POST_SAVE       => ['onLeadSave', -1],
        ];
    }

    /**
     * Trigger applicable events for the lead.
     */
    public function onLeadPointsChange(PointsChangeEvent $event)
    {
        $this->triggerModel->triggerEvents($event->getLead());
    }

    /**
     * Handle point triggers for new leads (including 0 point triggers).
     */
    public function onLeadSave(LeadEvent $event)
    {
        if ($event->isNew()) {
            $this->triggerModel->triggerEvents($event->getLead());
        }
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
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
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$log['id'],
                        'eventLabel' => $log['eventName'].' / '.$log['delta'],
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $log['dateAdded'],
                        'extra'      => [
                            'log' => $log,
                        ],
                        'icon'      => 'fa-calculator',
                        'contactId' => $log['lead_id'],
                    ]
                );
            }
        }
    }

    public function onLeadMerge(LeadMergeEvent $event)
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
