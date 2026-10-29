<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class SearchSubscriber
{
    public function __construct(
        private CampaignModel $campaignModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->campaignModel,
            '@MauticCampaign/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.campaign.campaigns', $results);
        }
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: MauticEvents\CommandListEvent::class)]
    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted('campaign:campaigns:view')) {
            $event->addCommands(
                'mautic.campaign.campaigns',
                $this->campaignModel->getCommandList()
            );
        }
    }
}
