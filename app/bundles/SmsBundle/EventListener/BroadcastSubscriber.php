<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\EventListener;

use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\SmsBundle\Broadcast\BroadcastExecutioner;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class BroadcastSubscriber
{
    public function __construct(
        private BroadcastExecutioner $broadcastExecutioner,
    ) {
    }

    #[AsEventListener(
        event: ChannelBroadcastEvent::class,
    )]
    public function onBroadcast(ChannelBroadcastEvent $event): void
    {
        if (!$event->checkContext('sms')) {
            return;
        }

        $this->broadcastExecutioner->execute($event);
    }
}
