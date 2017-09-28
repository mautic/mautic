<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
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
     * Compile events for the lead timeline.
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addChannelMessageEvents($event);
    }

    /**
     * @param LeadTimelineEvent $event
     * @param                   $state
     */
    protected function addChannelMessageEvents(LeadTimelineEvent $event)
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

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepository */
        $messageQueueRepository = $this->em->getRepository('MauticChannelBundle:MessageQueue');
        $logs                   = $messageQueueRepository->getLeadTimelineEvents($event->getLeadId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($logs['results'] as $log) {
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventLabel' => $label.$log['channelName'],
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $log['dateAdded'],
                        'extra'      => [
                            'log' => $log,
                        ],
                        'icon'      => 'fa-comments-o',
                        'contactId' => $log['lead_id'],
                    ]
                );
            }
        }
    }
}
