<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Stats;

use Mautic\CampaignBundle\Model\CampaignModel;

class TagDependencies
{
    public function __construct(
        private CampaignModel $campaignModel
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getChannelsIds(string $tagName): array
    {
        return [
            [
                'label' => 'mautic.campaign.campaigns',
                'route' => 'mautic_campaign_index',
                'ids'   => $this->campaignModel->getCampaignIdsWithDependenciesOnTagName($tagName),
            ],
        ];
    }
}
