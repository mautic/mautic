<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Stats;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\Model\TriggerEventModel;
use Mautic\ReportBundle\Model\ReportModel;

class EmailDependencies
{
    public function __construct(
        private CampaignModel $campaignModel,
        private ListModel $listModel,
        private ActionModel $actionModel,
        private PointModel $pointModel,
        private TriggerEventModel $triggerEventModel,
        private ReportModel $reportModel,
    ) {
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
            [
                'label' => 'mautic.lead.lead.lists',
                'route' => 'mautic_segment_index',
                'ids'   => $this->listModel->getSegmentIdsWithDependenciesOnEmail($emailId),
            ],
            [
                'label' => 'mautic.form.forms',
                'route' => 'mautic_form_index',
                'ids'   => $this->actionModel->getFormsIdsWithDependenciesOnEmail($emailId),
            ],
            [
                'label' => 'mautic.point.actions.header.index',
                'route' => 'mautic_point_index',
                'ids'   => $this->pointModel->getPointActionIdsWithDependenciesOnEmail($emailId),
            ],
            [
                'label' => 'mautic.point.trigger.header.index',
                'route' => 'mautic_pointtrigger_index',
                'ids'   => $this->triggerEventModel->getPointTriggerIdsWithDependenciesOnEmail($emailId),
            ],
            [
                'label' => 'mautic.report.reports',
                'route' => 'mautic_report_index',
                'ids'   => $this->reportModel->getReportsIdsWithDependenciesOnEmail($emailId),
            ],
        ];
    }
}
