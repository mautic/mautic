<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;

/**
 * Class ReportSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class ReportSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            ReportEvents::REPORT_ON_BUILD          => array('onReportBuilder', 0),
            ReportEvents::REPORT_ON_GENERATE       => array('onReportGenerate', 0),
            ReportEvents::REPORT_ON_GRAPH_GENERATE => array('onReportGraphGenerate', 0)
        );
    }

    /**
     * Add available tables and columns to the report builder lookup
     *
     * @param ReportBuilderEvent $event
     *
     * @return void
     */
    public function onReportBuilder (ReportBuilderEvent $event)
    {
        if ($event->checkContext(array('emails', 'email.stats'))) {
            $prefix        = 'e.';
            $variantParent = 'vp.';
            $columns       = array(
                $prefix . 'subject'            => array(
                    'label' => 'mautic.email.subject',
                    'type'  => 'string'
                ),
                $prefix . 'lang'               => array(
                    'label' => 'mautic.core.language',
                    'type'  => 'string'
                ),
                $prefix . 'read_count'         => array(
                    'label' => 'mautic.email.report.read_count',
                    'type'  => 'int'
                ),
                $prefix . 'revision'           => array(
                    'label' => 'mautic.email.report.revision',
                    'type'  => 'int'
                ),
                $variantParent . 'id'          => array(
                    'label' => 'mautic.email.report.variant_parent_id',
                    'type'  => 'int'
                ),
                $variantParent . 'subject'     => array(
                    'label' => 'mautic.email.report.variant_parent_subject',
                    'type'  => 'string'
                ),
                $prefix . 'variant_start_date' => array(
                    'label' => 'mautic.email.report.variant_start_date',
                    'type'  => 'datetime'
                ),
                $prefix . 'variant_sent_count' => array(
                    'label' => 'mautic.email.report.variant_sent_count',
                    'type'  => 'int'
                ),
                $prefix . 'variant_read_count' => array(
                    'label' => 'mautic.email.report.variant_read_count',
                    'type'  => 'int'
                )
            );
            $columns       = array_merge($columns, $event->getStandardColumns($prefix), $event->getCategoryColumns());
            $data          = array(
                'display_name' => 'mautic.email.emails',
                'columns'      => $columns
            );
            $event->addTable('emails', $data);

            if ($event->checkContext('email.stats')) {
                $statPrefix  = 'es.';
                $statColumns = array(
                    $statPrefix . 'email_address'     => array(
                        'label' => 'mautic.email.report.stat.email_address',
                        'type'  => 'email'
                    ),
                    $statPrefix . 'date_sent'         => array(
                        'label' => 'mautic.email.report.stat.date_sent',
                        'type'  => 'datetime'
                    ),
                    $statPrefix . 'is_read'           => array(
                        'label' => 'mautic.email.report.stat.is_read',
                        'type'  => 'bool'
                    ),
                    $statPrefix . 'is_failed'         => array(
                        'label' => 'mautic.email.report.stat.is_failed',
                        'type'  => 'bool'
                    ),
                    $statPrefix . 'viewed_in_browser' => array(
                        'label' => 'mautic.email.report.stat.viewed_in_browser',
                        'type'  => 'bool'
                    ),
                    $statPrefix . 'date_read'         => array(
                        'label' => 'mautic.email.report.stat.date_read',
                        'type'  => 'datetime'
                    ),
                    $statPrefix . 'retry_count'       => array(
                        'label' => 'mautic.email.report.stat.retry_count',
                        'type'  => 'int'
                    ),
                    $statPrefix . 'source'            => array(
                        'label' => 'mautic.report.field.source',
                        'type'  => 'string'
                    ),
                    $statPrefix . 'source_id'         => array(
                        'label' => 'mautic.report.field.source_id',
                        'type'  => 'int'
                    )
                );

                $data = array(
                    'display_name' => 'mautic.email.stats.report.table',
                    'columns'      => array_merge($columns, $statColumns, $event->getLeadColumns(), $event->getIpColumn())
                );
                $event->addTable('email.stats', $data);

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
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGeneratorEvent $event
     *
     * @return void
     */
    public function onReportGenerate (ReportGeneratorEvent $event)
    {
        $context = $event->getContext();
        if ($context == 'emails') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'emails', 'e')
                ->leftJoin('e', MAUTIC_TABLE_PREFIX . 'emails', 'vp', 'vp.id = e.variant_parent_id');
            $event->addCategoryLeftJoin($qb, 'e');

            $event->setQueryBuilder($qb);
        } elseif ($context == 'email.stats') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'email_stats', 'es')
                ->leftJoin('es', MAUTIC_TABLE_PREFIX . 'emails', 'e', 'e.id = es.email_id')
                ->leftJoin('e', MAUTIC_TABLE_PREFIX . 'emails', 'vp', 'vp.id = e.variant_parent_id');
            $event->addCategoryLeftJoin($qb, 'e');
            $event->addLeadLeftJoin($qb, 'es');
            $event->addIpAddressLeftJoin($qb, 'es');

            $event->setQueryBuilder($qb);
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGeneratorEvent $event
     *
     * @return void
     */
    public function onReportGraphGenerate (ReportGraphEvent $event)
    {
        // Context check, we only want to fire for Lead reports
        if (!$event->checkContext('email.stats')) {
            return;
        }

        $graphs   = $event->getRequestedGraphs();
        $qb       = $event->getQueryBuilder();
        $statRepo = $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');

        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;
            $chartQuery   = clone $options['chartQuery'];
            $origQuery    = clone $queryBuilder;
            $chartQuery->applyDateFilters($queryBuilder, 'date_sent', 'es');

            switch ($g) {
                case 'mautic.email.graph.line.stats':
                    $chart        = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $sendQuery    = clone $queryBuilder;
                    $readQuery    = clone $origQuery;
                    $failedQuery  = clone $queryBuilder;
                    $failedQuery->andWhere($qb->expr()->eq('es.is_failed', ':true'));
                    $failedQuery->setParameter('true', true, 'boolean');
                    $chartQuery->applyDateFilters($readQuery, 'date_read', 'es');
                    $chartQuery->modifyTimeDataQuery($sendQuery, 'date_sent', 'es');
                    $chartQuery->modifyTimeDataQuery($readQuery, 'date_read', 'es');
                    $chartQuery->modifyTimeDataQuery($failedQuery, 'date_sent', 'es');
                    $sends        = $chartQuery->loadAndBuildTimeData($sendQuery);
                    $reads        = $chartQuery->loadAndBuildTimeData($readQuery);
                    $failes       = $chartQuery->loadAndBuildTimeData($failedQuery);
                    $chart->setDataset($options['translator']->trans('mautic.email.sent.emails'), $sends);
                    $chart->setDataset($options['translator']->trans('mautic.email.read.emails'), $reads);
                    $chart->setDataset($options['translator']->trans('mautic.email.failed.emails'), $failes);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;

                case 'mautic.email.graph.pie.ignored.read.failed':
                    $counts                 = $statRepo->getIgnoredReadFailed($queryBuilder);
                    $chart  = new PieChart();
                    $chart->setDataset($options['translator']->trans('mautic.email.read.emails'), $counts['read']);
                    $chart->setDataset($options['translator']->trans('mautic.email.failed.emails'), $counts['failed']);
                    $chart->setDataset($options['translator']->trans('mautic.email.ignored.emails'), $counts['ignored']);
                    $event->setGraph(
                        $g,
                        array(
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-flag-checkered'
                        )
                    );
                    break;

                case 'mautic.email.table.most.emails.sent':
                    $queryBuilder->select('e.id, e.subject as title, count(es.id) as sent')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('sent', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $statRepo->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = array();
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
                    $graphData              = array();
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
                    $graphData              = array();
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
                    $graphData              = array();
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