<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\SmsBundle\Broadcast\BroadcastExecutioner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BroadcastSubscriber.
 */
class BroadcastSubscriber implements EventSubscriberInterface
{
    /**
     * @var BroadcastExecutioner
     */
    private $broadcastExecutioner;

    /**
     * BroadcastSubscriber constructor.
     */
    public function __construct(BroadcastExecutioner $broadcastExecutioner)
    {
        $this->broadcastExecutioner = $broadcastExecutioner;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::CHANNEL_BROADCAST => ['onBroadcast', 0],
        ];
    }

    public function onBroadcast(ChannelBroadcastEvent $event)
    {
        if (!$event->checkContext('sms')) {
            return;
        }

        $this->broadcastExecutioner->execute($event);
    }
}
