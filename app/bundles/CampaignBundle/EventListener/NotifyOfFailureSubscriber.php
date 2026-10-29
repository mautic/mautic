<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CampaignBundle\Event\NotifyOfFailureEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;

final readonly class NotifyOfFailureSubscriber
{
    public function __construct(
        private NotificationHelper $notificationHelper,
    ) {
    }

    #[AsEventListener(event: NotifyOfFailureEvent::class)]
    public function notifyOfFailure(NotifyOfFailureEvent $event): void
    {
        $this->notificationHelper->notifyOfFailure($event->getLead(), $event->getFailedEvent());
    }
}
