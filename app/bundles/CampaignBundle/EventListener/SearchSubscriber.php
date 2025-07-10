<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::GLOBAL_SEARCH      => ['onGlobalSearch', 0],
            CoreEvents::BUILD_COMMAND_LIST => ['onBuildCommandList', 0],
        ];
    }

    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->campaignModel,
            '@MauticCampaign/SubscribedEvents/Search/global.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.campaign.campaigns', $results);
        }
    }

    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        $security = $this->security;
        if ($security->isGranted('campaign:campaigns:view')) {
            $event->addCommands(
                'mautic.campaign.campaigns',
                $this->campaignModel->getCommandList()
            );
        }
    }
}
