<?php

namespace Mautic\StageBundle\EventListener;

use Mautic\LeadBundle\Entity\StagesChangeLogRepository;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\StageBundle\Entity\LeadStageLogRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * @var StagesChangeLogRepository
     */
    private $stagesChangeLogRepository;

    /**
     * @var LeadStageLogRepository
     */
    private $leadStageLogRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        StagesChangeLogRepository $stagesChangeLogRepository,
        LeadStageLogRepository $leadStageLogRepository,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->stagesChangeLogRepository = $stagesChangeLogRepository;
        $this->leadStageLogRepository    = $leadStageLogRepository;
        $this->translator                = $translator;
        $this->router                    = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey  = 'stage.changed';
        $eventTypeName = $this->translator->trans('mautic.stage.event.changed');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('stageList');

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $logs = $this->stagesChangeLogRepository->getLeadTimelineEvents($event->getLeadId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($logs['results'] as $log) {
                if (isset($log['reference']) && null != $log['reference']) {
                    $eventLabel = [
                        'label'      => $log['eventName'],
                        'href'       => $this->router->generate('mautic_stage_action', ['objectAction' => 'edit', 'objectId' => $log['reference']]),
                        'isExternal' => false,
                    ];
                } else {
                    $eventLabel = $log['eventName'];
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
                        'icon'      => 'fa-tachometer',
                        'contactId' => $log['lead_id'],
                    ]
                );
            }
        }
    }

    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->leadStageLogRepository->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}
