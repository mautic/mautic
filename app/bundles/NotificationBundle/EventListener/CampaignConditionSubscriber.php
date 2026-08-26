<?php

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\ConditionEvent;
use Mautic\NotificationBundle\Entity\PushID;
use Mautic\NotificationBundle\NotificationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignConditionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CampaignBuilderEvent::class => ['onCampaignBuild', 0],
            NotificationEvents::ON_CAMPAIGN_TRIGGER_CONDITION => ['onCampaignTriggerHasActiveCondition', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addCondition(
            'notification.has.active',
            [
                'label'       => 'mautic.notification.campaign.event.notification.has.active',
                'description' => 'mautic.notification.campaign.event.notification.has.active.desc',
                'eventName'   => NotificationEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
            ]
        );
    }

    public function onCampaignTriggerHasActiveCondition(ConditionEvent $event): void
    {
        if (!$event->checkContext('notification.has.active')) {
            return;
        }

        $pushIds = $event->getLead()->getPushIDs();
        /** @var PushID $pushID */
        foreach ($pushIds as $pushID) {
            if ($pushID->isEnabled()) {
                $event->pass();

                return;
            }
        }

        $event->fail();
    }
}
