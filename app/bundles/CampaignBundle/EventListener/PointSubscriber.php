<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Form\Type\CampaignEventAddRemoveLeadType;
use Mautic\CampaignBundle\Helper\CampaignEventHelper;
use Mautic\PointBundle\Event\TriggerBuilderEvent;

final class PointSubscriber
{
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: TriggerBuilderEvent::class,
        priority: 0
    )]
    public function onTriggerBuild(TriggerBuilderEvent $event): void
    {
        $changeLists = [
            'group'    => 'mautic.campaign.point.trigger',
            'label'    => 'mautic.campaign.point.trigger.changecampaigns',
            'callback' => [CampaignEventHelper::class, 'addRemoveLead'],
            'formType' => CampaignEventAddRemoveLeadType::class,
        ];

        $event->addEvent('campaign.changecampaign', $changeLists);
    }
}
