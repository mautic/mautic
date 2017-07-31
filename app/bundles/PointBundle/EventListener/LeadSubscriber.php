<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PointBundle\Model\TriggerModel;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var TriggerModel
     */
    protected $triggerModel;

    /**
     * LeadSubscriber constructor.
     *
     * @param TriggerModel $triggerModel
     */
    public function __construct(TriggerModel $triggerModel)
    {
        $this->triggerModel = $triggerModel;
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
     *
     * @param PointsChangeEvent $event
     */
    public function onLeadPointsChange(PointsChangeEvent $event)
    {
        $this->triggerModel->triggerEvents($event->getLead());
    }

    /**
     * Handle point triggers for new leads (including 0 point triggers).
     *
     * @param LeadEvent $event
     */
    public function onLeadSave(LeadEvent $event)
    {
        if ($event->isNew()) {
            $this->triggerModel->triggerEvents($event->getLead());
        }
    }

    /**
     * Compile events for the lead timeline.
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey  = 'point.gained';
        $eventTypeName = $this->translator->trans('mautic.point.event.gained');
        $event->addEventType($eventTypeKey, $eventTypeName);

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        /** @var \Mautic\PageBundle\Entity\HitRepository $hitRepository */
        $logRepository = $this->em->getRepository('MauticLeadBundle:PointsChangeLog');
        $logs          = $logRepository->getLeadTimelineEvents($event->getLeadId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($logs['results'] as $log) {
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventLabel' => $log['eventName'].' / '.$log['delta'],
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $log['dateAdded'],
                        'extra'      => [
                            'log' => $log,
                        ],
                        'icon' => 'fa-calculator',
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
        $this->em->getRepository('MauticPointBundle:LeadPointLog')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $this->em->getRepository('MauticPointBundle:LeadTriggerLog')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}
