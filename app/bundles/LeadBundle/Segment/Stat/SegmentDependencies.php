<?php

namespace Mautic\LeadBundle\Segment\Stat;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PointBundle\Model\TriggerEventModel;
use Mautic\ReportBundle\Model\ReportModel;

final readonly class SegmentDependencies
{
    public function __construct(
        private EmailModel $emailModel,
        private CampaignModel $campaignModel,
        private ActionModel $actionModel,
        private ListModel $listModel,
        private TriggerEventModel $triggerEventModel,
        private ReportModel $reportModel,
    ) {
    }

    public function getChannelsIds($segmentId): array
    {
        return [
            [
                'label' => 'mautic.email.emails',
                'route' => 'mautic_email_index',
                'ids'   => $this->emailModel->getEmailsIdsWithDependenciesOnSegment($segmentId),
            ], [
                'label' => 'mautic.campaign.campaigns',
                'route' => 'mautic_campaign_index',
                'ids'   => $this->campaignModel->getCampaignIdsWithDependenciesOnSegment($segmentId),
            ], [
                'label' => 'mautic.lead.lead.lists',
                'route' => 'mautic_segment_index',
                'ids'   => $this->listModel->getSegmentsWithDependenciesOnSegment($segmentId, 'id'),
            ], [
                'label' => 'mautic.report.reports',
                'route' => 'mautic_report_index',
                'ids'   => $this->reportModel->getReportsIdsWithDependenciesOnSegment($segmentId),
            ], [
                'label' => 'mautic.form.forms',
                'route' => 'mautic_form_index',
                'ids'   => $this->actionModel->getFormsIdsWithDependenciesOnSegment($segmentId),
            ], [
                'label' => 'mautic.point.trigger.header.index',
                'route' => 'mautic_pointtrigger_index',
                'ids'   => $this->triggerEventModel->getReportIdsWithDependenciesOnSegment($segmentId),
            ],
        ];
    }
}
