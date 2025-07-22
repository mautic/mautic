<?php

namespace Mautic\ReportBundle\EventListener;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Form\Type\ReportWidgetType;
use Mautic\ReportBundle\Model\ReportModel;

class DashboardSubscriber extends MainDashboardSubscriber
{
    private const TABLE_ROW_LIMIT = 10;

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
        protected CorePermissions $security,
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
                    $reportOptions = $this->prepareReportOptions($graph, $params);
                    $reportData    = $this->reportModel->getReportData($report, null, $reportOptions);

                    if (isset($reportData['graphs'][$graph])) {
                        $graphData = $reportData['graphs'][$graph];
                        if (!isset($graphData['data'])) {
                            $graphData['data'] = $reportData['data'];
                        }

                        $templateData = $this->prepareTemplateData($graphData, $widget, $report, $params, $reportData['columns']);
                        $event->setTemplateData($templateData);
                    }
                }
            }
            $event->setTemplate('@MauticReport/SubscribedEvents/Dashboard/widget.html.twig');
            $event->stopPropagation();
        }
    }

    /**
     * @param array<string, int|string|bool|array<string, mixed>> $params
     *
     * @return array<string, int|string|bool|\DateTime>
     */
    private function prepareReportOptions(string $graph, array $params): array
    {
        $graphData = $this->reportModel->getGraphData($params['graph']);
        $type      = $graphData[$graph]['type'] ?? 'table';

        $options = [
            'ignoreTableData' => 'table' !== $type,
            'graphName'       => $graph,
            'dateFrom'        => $params['dateFrom'],
            'dateTo'          => $params['dateTo'],
        ];

        if ('table' === $type) {
            $options['paginate'] = true;
            $options['limit']    = self::TABLE_ROW_LIMIT;
            $options['page']     = 1;
        }

        return $options;
    }

    /**
     * @param array<string, string|array<int|string, string|bool|int|mixed>> $graphData
     * @param array<string, int|string|bool|array<string, mixed>>            $params
     * @param string[][]                                                     $columns
     *
     * @return array<string, array<int|string, string[]|string>|bool|\DateTime|int|string>
     */
    private function prepareTemplateData(array $graphData, Widget $widget, Report $report, array $params, array $columns): array
    {
        $templateData = [
            'chartData'   => $graphData['data'],
            'chartType'   => $graphData['type'],
            'chartHeight' => $widget->getHeight() - 90,
            'reportId'    => $report->getId(),
            'dateFrom'    => $params['dateFrom'],
            'dateTo'      => $params['dateTo'],
        ];

        if ('table' === $graphData['type']) {
            $templateData['chartHeight'] = $widget->getHeight();
            $templateData['tableHeader'] = $this->getTableHeader($columns, $report->getColumns());
        }

        return $templateData;
    }

    /**
     * @param string[][]                $columns
     * @param array<int|string, string> $columnOrder
     *
     * @return array<int, string>
     */
    private function getTableHeader(array $columns, array $columnOrder): array
    {
        return array_values(array_filter(
            array_map(fn ($key) => $columns[$key]['label'] ?? null, $columnOrder)
        ));
    }
}
