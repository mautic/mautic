<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Form\Type\CampaignEventAddRemoveLeadType;
use Mautic\CampaignBundle\Helper\CampaignEventHelper;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class PointSubscriber
{
    #[AsEventListener(
        event: TriggerBuilderEvent::class,
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
