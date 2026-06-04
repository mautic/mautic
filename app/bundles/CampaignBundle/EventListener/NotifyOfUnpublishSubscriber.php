<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotifyOfUnpublishSubscriber implements EventSubscriberInterface
{
    public function __construct(private NotificationHelper $notificationHelper)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::ON_CAMPAIGN_UNPUBLISH_NOTIFY => 'notifyOfUnpublish',
        ];
    }

    public function notifyOfUnpublish(NotifyOfUnpublishEvent $event): void
    {
        $this->notificationHelper->notifyOfUnpublish($event->getFailedEvent());
    }
}
