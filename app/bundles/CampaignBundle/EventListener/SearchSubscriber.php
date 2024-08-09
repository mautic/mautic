<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private CorePermissions $security,
        private Environment $twig
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
        if ($this->security->isGranted('campaign:campaigns:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $campaigns = $this->campaignModel->getEntities(
                [
                    'limit'  => 5,
                    'filter' => $str,
                ]);

            if (count($campaigns) > 0) {
                $campaignResults = [];
                foreach ($campaigns as $campaign) {
                    $campaignResults[] = $this->twig->render(
                        '@MauticCampaign/SubscribedEvents/Search/global.html.twig',
                        [
                            'campaign' => $campaign,
                        ]
                    );
                }
                if (count($campaigns) > 5) {
                    $campaignResults[] = $this->twig->render(
                        '@MauticCampaign/SubscribedEvents/Search/global.html.twig',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($campaigns) - 5),
                        ]
                    );
                }
                $campaignResults['count'] = count($campaigns);
                $event->addResults('mautic.campaign.campaigns', $campaignResults);
            }
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
