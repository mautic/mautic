<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\NotificationBundle\Entity\PushID;
use Mautic\NotificationBundle\NotificationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CampaignConditionSubscriber.
 */
class CampaignConditionSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                 => ['onCampaignBuild', 0],
            NotificationEvents::ON_CAMPAIGN_TRIGGER_CONDITION => ['onCampaignTriggerHasActiveCondition', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
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

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerHasActiveCondition(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('notification.has.active')) {
            return;
        }

        $pushIds = $event->getLead()->getPushIDs();
        /** @var PushID $pushID */
        foreach ($pushIds as $pushID) {
            if ($pushID->isEnabled()) {
                return $event->setResult(true);
            }
        }

        return $event->setResult(false);
    }
}
