<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\NotifyOfFailureEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotifyOfFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(private NotificationHelper $notificationHelper)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::ON_CAMPAIGN_FAILURE_NOTIFY => 'notifyOfFailure',
        ];
    }

    public function notifyOfFailure(NotifyOfFailureEvent $event): void
    {
        $this->notificationHelper->notifyOfFailure($event->getLead(), $event->getFailedEvent());
    }
}
