<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TimelineEventLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface|Translator
     */
    private $translator;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

    /**
     * TimelineEventLogSubscriber constructor.
     *
     * @param TranslatorInterface    $translator
     * @param ModelFactory           $modelFactory
     * @param LeadEventLogRepository $leadEventLogRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        LeadEventLogRepository $leadEventLogRepository
    ) {
        $this->translator             = $translator;
        $this->leadEventLogRepository = $leadEventLogRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addEvents($event, 'lead.source.created', 'mautic.lead.timeline.created_source');
        $this->addEvents($event, 'lead.source.identified', 'mautic.lead.timeline.identified_source');
    }

    /**
     * @param LeadTimelineEvent $event
     * @param                   $eventType
     * @param                   $eventTypeName
     */
    private function addEvents(LeadTimelineEvent $event, $eventType, $eventTypeName)
    {
        $eventTypeName = $this->translator->trans($eventTypeName);
        $event->addEventType($eventType, $eventTypeName);

        $action = str_replace('lead.source.', '', $eventType).'_contact';
        $events = $this->leadEventLogRepository->getEventsByAction($action, $event->getLead(), $event->getQueryOptions());

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
     * @param array $log
     * @param       $eventType
     * @param       $eventTypeName
     *
     * @return array
     */
    private function getEventEntry(array $log, $eventType, $eventTypeName)
    {
        return [
            'event'           => $eventType,
            'eventId'         => $eventType.$log['id'],
            'eventType'       => $eventTypeName,
            'eventLabel'      => $this->getSourceName($log, $eventType),
            'timestamp'       => $log['date_added'],
            'icon'            => ('lead.source.created' === $eventType) ? 'fa-user-secret' : 'fa-user',
            'contactId'       => $log['lead_id'],
        ];
    }

    /**
     * @param array $log
     * @param       $eventType
     *
     * @return string
     */
    private function getSourceName(array $log, $eventType)
    {
        $properties = json_decode($log['properties'], true);

        if (!empty($properties['object_description'])) {
            return $this->translator->trans(
                'mautic.lead.timeline.'.$eventType.'_by_object',
                [
                    '%name%' => $properties['object_description'],
                ]
            );
        }

        $customString = 'mautic.lead.timeline.source.'.$log['bundle'].'.'.$log['object'];
        if ($this->translator->hasId($customString)) {
            return $this->translator->trans($customString);
        }

        return $this->translator->trans(
            'mautic.lead.timeline.'.$eventType,
            [
                '%bundle%' => $log['bundle'],
                '%object%' => $log['object'],
            ]
        );
    }
}
