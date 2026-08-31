<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class NotifyOfUnpublishSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationHelper $notificationHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NotifyOfUnpublishEvent::class => 'notifyOfUnpublish',
        ];
    }

    public function notifyOfUnpublish(NotifyOfUnpublishEvent $event): void
    {
        $this->notificationHelper->notifyOfUnpublish($event->getFailedEvent());
    }
}
