<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TimelineSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LeadEventLogRepository $eventLogRepository,
        private TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $eventType     = 'integration_sync_issues';
        $eventTypeName = $this->translator->trans('mautic.integration.sync.timeline_notices');
        $event->addEventType($eventType, $eventTypeName);

        if (!$event->isApplicable($eventType)) {
            return;
        }

        $events = $this->eventLogRepository->getEvents($event->getLead(), 'integrations', null, 'sync', $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventType, $events);

        if ($event->isEngagementCount()) {
            return;
        }

        // Add the logs to the event array
        foreach ($events['results'] as $log) {
            $event->addEvent(
                $this->getEventEntry($log, $eventType, $eventTypeName)
            );
        }
    }

    /**
     * @param mixed[] $log
     *
     * @return mixed[]
     */
    private function getEventEntry(array $log, string $eventType, string $eventTypeName): array
    {
        $properties = json_decode($log['properties'], true);

        return [
            'event'           => $eventType,
            'eventId'         => $eventType.$log['id'],
            'eventType'       => $eventTypeName,
            'eventLabel'      => $this->translator->trans(
                'mautic.integration.sync.user_notification.header',
                [
                    '%integration%' => $properties['integration'],
                    '%object%'      => $properties['object'],
                ]
            ),
            'timestamp'       => $log['date_added'],
            'icon'            => 'fa-refresh',
            'contactId'       => $log['lead_id'],
            'contentTemplate' => '@Integrations/Timeline/index.html.twig',
            'extra'           => $properties,
        ];
    }
}
