<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\SmsBundle\Broadcast\BroadcastExecutioner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BroadcastSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BroadcastExecutioner $broadcastExecutioner,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvents::CHANNEL_BROADCAST => ['onBroadcast', 0],
        ];
    }

    public function onBroadcast(ChannelBroadcastEvent $event): void
    {
        if (!$event->checkContext('sms')) {
            return;
        }

        $this->broadcastExecutioner->execute($event);
    }
}
