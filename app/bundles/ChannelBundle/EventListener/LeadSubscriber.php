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

use Mautic\ChannelBundle\Entity\MessageQueueRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var MessageQueueRepository
     */
    private $messageQueueRepository;

    /**
     * @param TranslatorInterface    $translator
     * @param Router                 $router
     * @param MessageQueueRepository $messageQueueRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        Router $router,
        MessageQueueRepository $messageQueueRepository
    ) {
        $this->translator             = $translator;
        $this->router                 = $router;
        $this->messageQueueRepository = $messageQueueRepository;
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
                        'contentTemplate' => 'MauticChannelBundle:SubscribedEvents\Timeline:queued_messages.html.php',
                        'icon'            => 'fa-comments-o',
                        'contactId'       => $log['lead_id'],
                    ]
                );
            }
        }
    }
}
