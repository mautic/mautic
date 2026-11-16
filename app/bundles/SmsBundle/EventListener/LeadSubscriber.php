<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\SmsBundle\Entity\StatRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private StatRepository $statRepository,
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
        $this->addSmsEvents($event, 'sent');
        $this->addSmsEvents($event, 'failed');
    }

    private function addSmsEvents(LeadTimelineEvent $event, string $state): void
    {
        // Set available event types
        $eventTypeKey  = 'sms.'.$state;
        $eventTypeName = $this->translator->trans('mautic.sms.timeline.status.'.$state);
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('smsList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $queryOptions          = $event->getQueryOptions();
        $queryOptions['state'] = $state;
        $stats                 = $this->statRepository->getLeadStats($event->getLeadId(), $queryOptions);

        // Add total to counter
        $event->addToCounter($eventTypeKey, $stats);

        if (!$event->isEngagementCount()) {
            // Add the events to the event array
            foreach ($stats['results'] as $stat) {
                if (!empty($stat['sms_name'])) {
                    $label = $stat['sms_name'];
                } else {
                    $label = $this->translator->trans('mautic.sms.timeline.event.custom_sms');
                }

                $eventName = [
                    'label'      => $label,
                    'href'       => $this->router->generate('mautic_sms_action', ['objectAction'=>'view', 'objectId' => $stat['sms_id']]),
                ];
                if ('failed' === $state || 'sent' === $state) { // this is to get the correct column for date dateSent
                    $dateSent = 'sent';
                }

                $contactId = $stat['lead_id'];
                unset($stat['lead_id']);
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$stat['id'],
                        'eventLabel' => $eventName,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $stat['date'.ucfirst($dateSent)],
                        'extra'      => [
                            'stat'   => $stat,
                            'type'   => $state,
                        ],
                        'contentTemplate' => '@MauticSms/SubscribedEvents/Timeline/index.html.twig',
                        'icon'            => ('read' === $state) ? 'ri-chat-1-fill' : 'ri-message-2-fill',
                        'contactId'       => $contactId,
                    ]
                );
            }
        }
    }
}
