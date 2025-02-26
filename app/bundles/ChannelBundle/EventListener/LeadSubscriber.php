<?php

namespace Mautic\ChannelBundle\EventListener;

use Mautic\ChannelBundle\Entity\MessageQueueRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private MessageQueueRepository $messageQueueRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $eventTypeKey  = 'message.queue';
        $eventTypeName = $this->translator->trans('mautic.message.queue');

        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('messageQueueList');

        $label = $this->translator->trans('mautic.queued.channel');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $logs = $this->messageQueueRepository->getLeadTimelineEvents($event->getLeadId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($logs['results'] as $log) {
                $eventName = [
                    'label' => $label.$log['channelName'].' '.$log['channelId'],
                    'href'  => $this->router->generate('mautic_'.$log['channelName'].'_action', ['objectAction' => 'view', 'objectId' => $log['channelId']]),
                ];
                $event->addEvent(
                    [
                        'eventId'    => $eventTypeKey.$log['id'],
                        'event'      => $eventTypeKey,
                        'eventLabel' => $eventName,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $log['dateAdded'],
                        'extra'      => [
                            'log' => $log,
                        ],
                        'contentTemplate' => '@MauticChannel/SubscribedEvents/Timeline/queued_messages.html.twig',
                        'icon'            => 'ri-question-answer-line',
                        'contactId'       => $log['lead_id'],
                    ]
                );
            }
        }
    }
}
