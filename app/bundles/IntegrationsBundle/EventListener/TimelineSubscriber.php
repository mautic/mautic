<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TimelineSubscriber implements EventSubscriberInterface
{
    /**
     * @var LeadEventLogRepository
     */
    private $eventLogRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LeadEventLogRepository $eventLogRepository, TranslatorInterface $translator)
    {
        $this->eventLogRepository = $eventLogRepository;
        $this->translator         = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * @param      $eventType
     * @param      $eventTypeName
     * @param      $icon
     * @param null $bundle
     * @param null $object
     * @param null $action
     */
    private function addEvents(LeadTimelineEvent $event, $eventType, $eventTypeName, $icon, $bundle = null, $object = null, $action = null): void
    {
        $eventTypeName = $this->translator->trans($eventTypeName);
        $event->addEventType($eventType, $eventTypeName);

        if (!$event->isApplicable($eventType)) {
            return;
        }

        $events = $this->eventLogRepository->getEvents($event->getLead(), $bundle, $object, $action, $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventType, $events);

        if ($event->isEngagementCount()) {
            return;
        }

        // Add the logs to the event array
        foreach ($events['results'] as $log) {
            $event->addEvent(
                $this->getEventEntry($log, $eventType, $eventTypeName, $icon)
            );
        }
    }

    /**
     * @param $eventType
     * @param $eventTypeName
     * @param $icon
     *
     * @return array
     */
    private function getEventEntry(array $log, $eventType, $eventTypeName, $icon)
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
            'icon'            => $icon,
            'contactId'       => $log['lead_id'],
            'contentTemplate' => 'IntegrationsBundle:Timeline:index.html.php',
            'extra'           => $properties,
        ];
    }

    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $this->addEvents(
            $event,
            'integration_sync_issues',
            'mautic.integration.sync.timeline_notices',
            'fa-refresh',
            'integrations'
        );
    }
}
