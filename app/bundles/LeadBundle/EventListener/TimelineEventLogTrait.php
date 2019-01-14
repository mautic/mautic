<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Symfony\Component\Translation\TranslatorInterface;

trait TimelineEventLogTrait
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LeadEventLogRepository
     */
    private $eventLogRepository;

    /**
     * @param LeadTimelineEvent $event
     * @param                   $eventType
     * @param                   $eventTypeName
     * @param                   $icon
     * @param null              $bundle
     * @param null              $object
     * @param null              $action
     */
    private function addEvents(LeadTimelineEvent $event, $eventType, $eventTypeName, $icon, $bundle = null, $object = null, $action = null)
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
     * @param array $log
     * @param       $eventType
     * @param       $eventTypeName
     * @param       $icon
     *
     * @return array
     */
    private function getEventEntry(array $log, $eventType, $eventTypeName, $icon)
    {
        return [
            'event'           => $eventType,
            'eventId'         => $eventType.$log['id'],
            'eventType'       => $eventTypeName,
            'eventLabel'      => $this->getSourceName($log, $eventType),
            'timestamp'       => $log['date_added'],
            'icon'            => $icon,
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
            $customString = 'mautic.lead.timeline.'.$eventType.'_by_object';
            if ($this->translator->hasId($customString)) {
                return $this->translator->trans(
                    $customString,
                    [
                        '%name%' => $properties['object_description'],
                    ]
                );
            }

            $customString = 'mautic.lead.timeline.'.$eventType.'_'.$log['action'].'_by_object';
            if ($this->translator->hasId($customString)) {
                return $this->translator->trans(
                    $customString,
                    [
                        '%name%' => $properties['object_description'],
                    ]
                );
            }
        }

        $customString = 'mautic.lead.timeline.'.$log['bundle'].'.'.$log['object'];
        if ($this->translator->hasId($customString)) {
            return $this->translator->trans($customString);
        }

        $customString = 'mautic.lead.timeline.'.$log['bundle'].'.'.$log['object'].'.'.$log['action'];
        if ($this->translator->hasId($customString)) {
            return $this->translator->trans($customString);
        }

        return $this->translator->trans(
            'mautic.lead.timeline.'.$eventType,
            [
                '%bundle%' => $log['bundle'],
                '%object%' => $log['object'],
                '%action%' => $log['action'],
            ]
        );
    }
}
