<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber.
 */
class ReportSubscriber extends CommonSubscriber
{
    const CONTEXT_ASSET          = 'asset';
    const CONTEXT_ASSET_DOWNLOAD = 'asset.downloads';

    /**
     * @var CompanyReportData
     */
    private $companyReportData;

    public function __construct(CompanyReportData $companyReportData)
    {
        $this->companyReportData = $companyReportData;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_GRAPH_GENERATE => ['onReportGraphGenerate', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     *
     * @param ReportBuilderEvent $event
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if ($event->checkContext([self::CONTEXT_ASSET, self::CONTEXT_ASSET_DOWNLOAD])) {
            // Assets
            $prefix  = 'a.';
            $columns = [
                $prefix.'download_count' => [
                    'label' => 'mautic.asset.report.download_count',
                    'type'  => 'int',
                ],
                $prefix.'unique_download_count' => [
                    'label' => 'mautic.asset.report.unique_download_count',
                    'type'  => 'int',
                ],
                $prefix.'alias' => [
                    'label' => 'mautic.core.alias',
                    'type'  => 'string',
                ],
                $prefix.'lang' => [
                    'label' => 'mautic.core.language',
                    'type'  => 'string',
                ],
                $prefix.'title' => [
                    'label' => 'mautic.core.title',
                    'type'  => 'string',
                ],
            ];

            $columns = array_merge(
                $columns,
                $event->getStandardColumns($prefix, ['name'], 'mautic_asset_action'),
                $event->getCategoryColumns(),
                $event->getCampaignByChannelColumns()
            );

            $event->addTable(
                self::CONTEXT_ASSET,
                [
                    'display_name' => 'mautic.asset.assets',
                    'columns'      => $columns,
                ]
            );

            if ($event->checkContext([self::CONTEXT_ASSET_DOWNLOAD])) {
                // Downloads
                $downloadPrefix  = 'ad.';
                $downloadColumns = [
                    $downloadPrefix.'date_download' => [
                        'label'          => 'mautic.asset.report.download.date_download',
                        'type'           => 'datetime',
                        'groupByFormula' => 'DATE('.$downloadPrefix.'date_download)',
                    ],
                    $downloadPrefix.'code' => [
                        'label' => 'mautic.asset.report.download.code',
                        'type'  => 'string',
                    ],
                    $downloadPrefix.'referer' => [
                        'label' => 'mautic.core.referer',
                        'type'  => 'string',
                    ],
                    $downloadPrefix.'source' => [
                        'label' => 'mautic.report.field.source',
                        'type'  => 'string',
                    ],
                    $downloadPrefix.'source_id' => [
                        'label' => 'mautic.report.field.source_id',
                        'type'  => 'int',
                    ],
                ];

                $companyColumns = $this->companyReportData->getCompanyData();

                $event->addTable(
                    self::CONTEXT_ASSET_DOWNLOAD,
                    [
                        'display_name' => 'mautic.asset.report.downloads.table',
                        'columns'      => array_merge(
                            $columns,
                            $downloadColumns,
                            $event->getLeadColumns(),
                            $event->getIpColumn(),
                            $companyColumns
                        ),
                        'filters' => $companyColumns,
                    ],
                    self::CONTEXT_ASSET
                );

                // Add Graphs
                $context = self::CONTEXT_ASSET_DOWNLOAD;
                $event->addGraph($context, 'line', 'mautic.asset.graph.line.downloads');
                $event->addGraph($context, 'table', 'mautic.asset.table.most.downloaded');
                $event->addGraph($context, 'table', 'mautic.asset.table.top.referrers');
                $event->addGraph($context, 'pie', 'mautic.asset.graph.pie.statuses', ['translate' => false]);
            }
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     *
     * @param ReportGeneratorEvent $event
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        $queryBuilder = $event->getQueryBuilder();

        if ($event->checkContext(self::CONTEXT_ASSET)) {
            $queryBuilder->from(MAUTIC_TABLE_PREFIX.'assets', 'a');
            $event->addCategoryLeftJoin($queryBuilder, 'a');
        } elseif ($event->checkContext(self::CONTEXT_ASSET_DOWNLOAD)) {
            $event->applyDateFilters($queryBuilder, 'date_download', 'ad');

            $queryBuilder->from(MAUTIC_TABLE_PREFIX.'asset_downloads', 'ad')
                ->leftJoin('ad', MAUTIC_TABLE_PREFIX.'assets', 'a', 'a.id = ad.asset_id');
            $event->addCategoryLeftJoin($queryBuilder, 'a');
            $event->addLeadLeftJoin($queryBuilder, 'ad');
            $event->addIpAddressLeftJoin($queryBuilder, 'ad');
            $event->addCampaignByChannelJoin($queryBuilder, 'a', 'asset');

            if ($this->companyReportData->eventHasCompanyColumns($event)) {
                $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', 'companies_lead', 'l.id = companies_lead.lead_id');
                $queryBuilder->leftJoin('companies_lead', MAUTIC_TABLE_PREFIX.'companies', 'comp', 'companies_lead.company_id = comp.id');
            }
        }

        $event->setQueryBuilder($queryBuilder);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     *
     * @param ReportGraphEvent $event
     */
    public function onReportGraphGenerate(ReportGraphEvent $event)
    {
        // Context check, we only want to fire for Lead reports
        if (!$event->checkContext(self::CONTEXT_ASSET_DOWNLOAD)) {
            return;
        }

        $graphs       = $event->getRequestedGraphs();
        $qb           = $event->getQueryBuilder();
        $downloadRepo = $this->em->getRepository(Download::class);

        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;
            $chartQuery   = clone $options['chartQuery'];
            $chartQuery->applyDateFilters($queryBuilder, 'date_download', 'ad');

            switch ($g) {
                case 'mautic.asset.graph.line.downloads':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_download', 'ad');
                    $downloads = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans($g), $downloads);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;
                case 'mautic.asset.table.most.downloaded':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $downloadRepo->getMostDownloaded($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-download';
                    $graphData['link']      = 'mautic_asset_action';
                    $event->setGraph($g, $graphData);
                    break;
                case 'mautic.asset.table.top.referrers':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $downloadRepo->getTopReferrers($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-download';
                    $graphData['link']      = 'mautic_asset_action';
                    $event->setGraph($g, $graphData);
                    break;
                case 'mautic.asset.graph.pie.statuses':
                    $items                  = $downloadRepo->getHttpStatuses($queryBuilder);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-globe';
                    $event->setGraph($g, $graphData);
                    break;
            }

            unset($queryBuilder);
        }
    }
}
