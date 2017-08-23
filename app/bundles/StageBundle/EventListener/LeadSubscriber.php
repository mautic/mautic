<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\LeadBundle\Entity\StagesChangeLogRepository;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
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
     *
     * @param LeadTimelineEvent $event
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

        /** @var StagesChangeLogRepository $logRepository */
        $logRepository = $this->em->getRepository('MauticLeadBundle:StagesChangeLog');
        $logs          = $logRepository->getLeadTimelineEvents($event->getLeadId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($logs['results'] as $log) {
                if (isset($log['reference']) && $log['reference'] != null) {
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

    /**
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $em = $this->em;
        $em->getRepository('MauticStageBundle:LeadStageLog')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}
