<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Stats;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PointBundle\Model\TriggerEventModel;
use Mautic\ReportBundle\Model\ReportModel;

class TagDependencies
{
    public function __construct(
        private CampaignModel $campaignModel,
        private ListModel $listModel,
        private ActionModel $actionModel,
        private TriggerEventModel $triggerEventModel,
        private ReportModel $reportModel,
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
            [
                'label' => 'mautic.form.forms',
                'route' => 'mautic_form_index',
                'ids'   => $this->actionModel->getFormsIdsWithDependenciesOnTag($tag->getTag()),
            ],
            [
                'label' => 'mautic.point.trigger.header.index',
                'route' => 'mautic_pointtrigger_index',
                'ids'   => $this->triggerEventModel->getPointTriggerIdsWithDependenciesOnTag($tag->getTag()),
            ],
            [
                'label' => 'mautic.report.reports',
                'route' => 'mautic_report_index',
                'ids'   => $this->reportModel->getReportsIdsWithDependenciesOnTag($tag->getId()),
            ],
        ];
    }
}
