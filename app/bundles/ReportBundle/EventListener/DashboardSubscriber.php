<?php

namespace Mautic\ReportBundle\EventListener;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\ReportBundle\Form\Type\ReportWidgetType;
use Mautic\ReportBundle\Model\ReportModel;

class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle = 'report';

    /**
     * Define the widget(s).
     *
     * @var string
     */
    protected $types = [
        'report' => [
            'formAlias' => ReportWidgetType::class,
        ],
    ];

    /**
     * Define permissions to see those widgets.
     *
     * @var array
     */
    protected $permissions = [
        'report:reports:viewown',
        'report:reports:viewother',
    ];

    public function __construct(
        protected ReportModel $reportModel,
        protected CorePermissions $security
    ) {
    }

    /**
     * Set a widget detail when needed.
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event): void
    {
        $this->checkPermissions($event);

        if ('report' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();
            if (!$event->isCached()) {
                [$reportId, $graph]     = explode(':', $params['graph']);
                $report                 = $this->reportModel->getEntity($reportId);

                if ($report && $this->security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $report->getCreatedBy())) {
                    $reportData = $this->reportModel->getReportData(
                        $report,
                        null,
                        [
                            'ignoreTableData' => true,
                            'graphName'       => $graph,
                            'dateFrom'        => $params['dateFrom'],
                            'dateTo'          => $params['dateTo'],
                        ]
                    );

                    if (isset($reportData['graphs'][$graph])) {
                        $graphData = $reportData['graphs'][$graph];
                        if (!isset($graphData['data']['data'])) {
                            $graphData['data']['data'] = $graphData['data'];
                        }
                        $event->setTemplateData(
                            [
                                'chartData'   => $graphData['data'],
                                'chartType'   => $graphData['type'],
                                'chartHeight' => $widget->getHeight() - 90,
                                'reportId'    => $report->getId(),
                                'dateFrom'    => $params['dateFrom'],
                                'dateTo'      => $params['dateTo'],
                            ]
                        );
                    }
                }
            }
            $event->setTemplate('@MauticReport/SubscribedEvents/Dashboard/widget.html.twig');
            $event->stopPropagation();
        }
    }
}
