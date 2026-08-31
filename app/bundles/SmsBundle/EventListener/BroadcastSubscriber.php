<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\EventListener;

use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\SmsBundle\Broadcast\BroadcastExecutioner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class BroadcastSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BroadcastExecutioner $broadcastExecutioner,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChannelBroadcastEvent::class => ['onBroadcast', 0],
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
