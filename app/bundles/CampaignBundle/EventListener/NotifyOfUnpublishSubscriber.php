<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class NotifyOfUnpublishSubscriber
{
    public function __construct(
        private NotificationHelper $notificationHelper,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: NotifyOfUnpublishEvent::class)]
    public function notifyOfUnpublish(NotifyOfUnpublishEvent $event): void
    {
        $this->notificationHelper->notifyOfUnpublish($event->getFailedEvent());
    }
}
