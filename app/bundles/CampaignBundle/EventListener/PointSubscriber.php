<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Form\Type\CampaignEventAddRemoveLeadType;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PointEvents::TRIGGER_ON_BUILD => ['onTriggerBuild', 0],
        ];
    }

    public function onTriggerBuild(TriggerBuilderEvent $event): void
    {
        $changeLists = [
            'group'    => 'mautic.campaign.point.trigger',
            'label'    => 'mautic.campaign.point.trigger.changecampaigns',
            'callback' => [\Mautic\CampaignBundle\Helper\CampaignEventHelper::class, 'addRemoveLead'],
            'formType' => CampaignEventAddRemoveLeadType::class,
        ];

        $event->addEvent('campaign.changecampaign', $changeLists);
    }
}
