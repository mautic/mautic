<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Stats;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\ListModel;

class TagDependencies
{
    public function __construct(
        private CampaignModel $campaignModel,
        private ListModel $listModel,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getChannelsIds(Tag $tag): array
    {
        return [
            [
                'label' => 'mautic.campaign.campaigns',
                'route' => 'mautic_campaign_index',
                'ids'   => $this->campaignModel->getCampaignIdsWithDependenciesOnTagName($tag->getTag()),
            ],
            [
                'label' => 'mautic.lead.lead.lists',
                'route' => 'mautic_segment_index',
                'ids'   => $this->listModel->getSegmentIdsWithDependenciesOnTag($tag->getId()),
            ],
        ];
    }
}
