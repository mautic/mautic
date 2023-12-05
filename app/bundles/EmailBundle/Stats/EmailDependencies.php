<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Stats;

use Mautic\CampaignBundle\Model\CampaignModel;

class EmailDependencies
{
    private CampaignModel $campaignModel;

    /**
     * SegmentCampaignShare constructor.
     */
    public function __construct(CampaignModel $campaignModel)
    {
        $this->campaignModel      = $campaignModel;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getChannelsIds(int $emailId): array
    {
        return [
            [
                'label' => 'mautic.campaign.campaigns',
                'route' => 'mautic_campaign_index',
                'ids'   => $this->campaignModel->getCampaignIdsWithDependenciesOnEmail($emailId),
            ],
        ];
    }
}
