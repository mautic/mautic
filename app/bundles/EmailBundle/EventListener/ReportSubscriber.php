<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber.
 */
class ReportSubscriber extends CommonSubscriber
{
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
        if ($event->checkContext(['emails', 'email.stats'])) {
            $prefix        = 'e.';
            $variantParent = 'vp.';
            $columns       = [
                $prefix.'subject' => [
                    'label' => 'mautic.email.subject',
                    'type'  => 'string',
                ],
                $prefix.'lang' => [
                    'label' => 'mautic.core.language',
                    'type'  => 'string',
                ],
                $prefix.'read_count' => [
                    'label' => 'mautic.email.report.read_count',
                    'type'  => 'int',
                ],
                $prefix.'revision' => [
                    'label' => 'mautic.email.report.revision',
                    'type'  => 'int',
                ],
                $variantParent.'id' => [
                    'label' => 'mautic.email.report.variant_parent_id',
                    'type'  => 'int',
                ],
                $variantParent.'subject' => [
                    'label' => 'mautic.email.report.variant_parent_subject',
                    'type'  => 'string',
                ],
                $prefix.'variant_start_date' => [
                    'label' => 'mautic.email.report.variant_start_date',
                    'type'  => 'datetime',
                ],
                $prefix.'variant_sent_count' => [
                    'label' => 'mautic.email.report.variant_sent_count',
                    'type'  => 'int',
                ],
                $prefix.'variant_read_count' => [
                    'label' => 'mautic.email.report.variant_read_count',
                    'type'  => 'int',
                ],
            ];
            $columns = array_merge($columns, $event->getStandardColumns($prefix, [], 'mautic_email_action'), $event->getCategoryColumns());
            $data    = [
                'display_name' => 'mautic.email.emails',
                'columns'      => $columns,
            ];
            $event->addTable('emails', $data);

            if ($event->checkContext('email.stats')) {
                $statPrefix  = 'es.';
                $statColumns = [
                    $statPrefix.'email_address' => [
                        'label' => 'mautic.email.report.stat.email_address',
                        'type'  => 'email',
                    ],
                    $statPrefix.'date_sent' => [
                        'label' => 'mautic.email.report.stat.date_sent',
                        'type'  => 'datetime',
                    ],
                    $statPrefix.'is_read' => [
                        'label' => 'mautic.email.report.stat.is_read',
                        'type'  => 'bool',
                    ],
                    $statPrefix.'is_failed' => [
                        'label' => 'mautic.email.report.stat.is_failed',
                        'type'  => 'bool',
                    ],
                    $statPrefix.'viewed_in_browser' => [
                        'label' => 'mautic.email.report.stat.viewed_in_browser',
                        'type'  => 'bool',
                    ],
                    $statPrefix.'date_read' => [
                        'label' => 'mautic.email.report.stat.date_read',
                        'type'  => 'datetime',
                    ],
                    $statPrefix.'retry_count' => [
                        'label' => 'mautic.email.report.stat.retry_count',
                        'type'  => 'int',
                    ],
                    $statPrefix.'source' => [
                        'label' => 'mautic.report.field.source',
                        'type'  => 'string',
                    ],
                    $statPrefix.'source_id' => [
                        'label' => 'mautic.report.field.source_id',
                        'type'  => 'int',
                    ],
                ];

                $data = [
                    'display_name' => 'mautic.email.stats.report.table',
                    'columns'      => array_merge($columns, $statColumns, $event->getLeadColumns(), $event->getIpColumn()),
                ];
                $event->addTable('email.stats', $data, 'emails');

                // Register Graphs
                $context = 'email.stats';
                $event->addGraph($context, 'line', 'mautic.email.graph.line.stats');
                $event->addGraph($context, 'pie', 'mautic.email.graph.pie.ignored.read.failed');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.sent');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.read');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.failed');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.read.percent');
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
        $context = $event->getContext();
        $qb      = $event->getQueryBuilder();

        switch ($context) {
            case 'emails':
                $qb->from(MAUTIC_TABLE_PREFIX.'emails', 'e')
                    ->leftJoin('e', MAUTIC_TABLE_PREFIX.'emails', 'vp', 'vp.id = e.variant_parent_id');
                $event->addCategoryLeftJoin($qb, 'e');
                break;
            case 'email.stats':
                $event->applyDateFilters($qb, 'date_sent', 'es');

                $qb->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
                    ->leftJoin('es', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = es.email_id')
                    ->leftJoin('e', MAUTIC_TABLE_PREFIX.'emails', 'vp', 'vp.id = e.variant_parent_id');
                $event->addCategoryLeftJoin($qb, 'e');
                $event->addLeadLeftJoin($qb, 'es');
                $event->addIpAddressLeftJoin($qb, 'es');
                break;
        }

        $event->setQueryBuilder($qb);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     *
     * @param ReportGraphEvent $event
     */
    public function onReportGraphGenerate(ReportGraphEvent $event)
    {
        // Context check, we only want to fire for Lead reports
        if (!$event->checkContext('email.stats')) {
            return;
        }

        $graphs   = $event->getRequestedGraphs();
        $qb       = $event->getQueryBuilder();
        $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');

        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;
            $chartQuery   = clone $options['chartQuery'];
            $origQuery    = clone $queryBuilder;
            $chartQuery->applyDateFilters($queryBuilder, 'date_sent', 'es');

            switch ($g) {
                case 'mautic.email.graph.line.stats':
                    $chart       = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $sendQuery   = clone $queryBuilder;
                    $readQuery   = clone $origQuery;
                    $failedQuery = clone $queryBuilder;
                    $failedQuery->andWhere($qb->expr()->eq('es.is_failed', ':true'));
                    $failedQuery->setParameter('true', true, 'boolean');
                    $chartQuery->applyDateFilters($readQuery, 'date_read', 'es');
                    $chartQuery->modifyTimeDataQuery($sendQuery, 'date_sent', 'es');
                    $chartQuery->modifyTimeDataQuery($readQuery, 'date_read', 'es');
                    $chartQuery->modifyTimeDataQuery($failedQuery, 'date_sent', 'es');
                    $sends  = $chartQuery->loadAndBuildTimeData($sendQuery);
                    $reads  = $chartQuery->loadAndBuildTimeData($readQuery);
                    $failes = $chartQuery->loadAndBuildTimeData($failedQuery);
                    $chart->setDataset($options['translator']->trans('mautic.email.sent.emails'), $sends);
                    $chart->setDataset($options['translator']->trans('mautic.email.read.emails'), $reads);
                    $chart->setDataset($options['translator']->trans('mautic.email.failed.emails'), $failes);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;

                case 'mautic.email.graph.pie.ignored.read.failed':
                    $counts = $statRepo->getIgnoredReadFailed($queryBuilder);
                    $chart  = new PieChart();
                    $chart->setDataset($options['translator']->trans('mautic.email.read.emails'), $counts['read']);
                    $chart->setDataset($options['translator']->trans('mautic.email.failed.emails'), $counts['failed']);
                    $chart->setDataset($options['translator']->trans('mautic.email.ignored.emails'), $counts['ignored']);
                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-flag-checkered',
                        ]
                    );
                    break;

                case 'mautic.email.table.most.emails.sent':
                    $queryBuilder->select('e.id, e.subject as title, count(es.id) as sent')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('sent', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $statRepo->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-paper-plane-o';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.read':
                    $queryBuilder->select('e.id, e.subject as title, count(CASE WHEN es.is_read THEN 1 ELSE null END) as "read"')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('"read"', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $statRepo->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-eye';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.failed':
                    $queryBuilder->select('e.id, e.subject as title, count(CASE WHEN es.is_failed THEN 1 ELSE null END) as failed')
                        ->having('count(CASE WHEN es.is_failed THEN 1 ELSE null END) > 0')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('failed', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $statRepo->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-exclamation-triangle';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.read.percent':
                    $queryBuilder->select('e.id, e.subject as title, round(e.read_count / e.sent_count * 100) as ratio')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('ratio', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $statRepo->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-tachometer';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;
            }
            unset($queryBuilder);
        }
    }
}
