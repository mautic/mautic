<?php

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\ConditionEvent;
use Mautic\NotificationBundle\Entity\PushID;
use Mautic\NotificationBundle\NotificationEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class CampaignConditionSubscriber
{
    #[AsEventListener(
        event: CampaignBuilderEvent::class,
    )]
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

    #[AsEventListener(
        event: NotificationEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
    )]
    public function onCampaignTriggerHasActiveCondition(ConditionEvent $event): void
    {
        if (!$event->checkContext('notification.has.active')) {
            return;
        }

        $lead    = $event->getLead();
        $pushIds = $lead->getPushIDs();
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
