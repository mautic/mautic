<?php

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\DynamicContentBundle\Entity\StatRepository;
use Mautic\LeadBundle\Event\LeadMergeEvent;
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
        private StatRepository $statRepository,
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
        // Set available event types
        $eventTypeKey      = 'dynamic.content.sent';
        $eventTypeNameSent = $this->translator->trans('mautic.dynamic.content.triggered');
        $event->addEventType($eventTypeKey, $eventTypeNameSent);
        $event->addSerializerGroup('dwcList');

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $stats = $this->statRepository->getLeadStats($event->getLeadId(), $event->getQueryOptions());

        // Add total number to counter
        $event->addToCounter($eventTypeKey, $stats);

        if (!$event->isEngagementCount()) {
            // Add the events to the event array
            foreach ($stats['results'] as $stat) {
                $contactId = $stat['lead_id'];
                unset($stat['lead_id']);
                if ($stat['dateSent']) {
                    $event->addEvent(
                        [
                            'event'      => $eventTypeKey,
                            'eventId'    => $eventTypeKey.$stat['id'],
                            'eventLabel' => [
                                'label' => $stat['name'],
                                'href'  => $this->router->generate(
                                    'mautic_dynamicContent_action',
                                    ['objectId' => $stat['dynamic_content_id'], 'objectAction' => 'view']
                                ),
                            ],
                            'eventType' => $eventTypeNameSent,
                            'timestamp' => $stat['dateSent'],
                            'extra'     => [
                                'stat' => $stat,
                                'type' => 'sent',
                            ],
                            'contentTemplate' => '@MauticDynamicContent/SubscribedEvents/Timeline/index.html.twig',
                            'icon'            => 'ri-puzzle-2-line',
                            'contactId'       => $contactId,
                        ]
                    );
                }
            }
        }
    }

    public function onLeadMerge(LeadMergeEvent $event): void
    {
        $this->statRepository->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}
