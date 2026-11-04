<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class NotifyOfUnpublishSubscriber
{
    public function __construct(
        private NotificationHelper $notificationHelper,
    ) {
    }

    #[AsEventListener(event: NotifyOfUnpublishEvent::class)]
    public function notifyOfUnpublish(NotifyOfUnpublishEvent $event): void
    {
        $this->notificationHelper->notifyOfUnpublish($event->getFailedEvent());
    }
}
