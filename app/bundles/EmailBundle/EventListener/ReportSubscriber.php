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

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\LeadBundle\Entity\DoNotContact;
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
     * @var Connection
     */
    protected $db;

    /**
     * ReportSubscriber constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
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
        if ($event->checkContext(['emails', 'email.stats'])) {
            $prefix               = 'e.';
            $variantParent        = 'vp.';
            $channelUrlTrackables = 'cut.';
            $doNotContact         = 'dnc.';
            $columns              = [
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
                'read_ratio' => [
                    'alias'   => 'read_ratio',
                    'label'   => 'mautic.email.report.read_ratio',
                    'type'    => 'string',
                    'formula' => 'CONCAT(ROUND(('.$prefix.'read_count/'.$prefix.'sent_count)*100, 1),\'%\')',
                ],
                $prefix.'sent_count' => [
                    'label' => 'mautic.email.report.sent_count',
                    'type'  => 'int',
                ],
                'hits' => [
                    'alias'   => 'hits',
                    'label'   => 'mautic.email.report.hits_count',
                    'type'    => 'string',
                    'formula' => $channelUrlTrackables.'hits',
                ],
                'unique_hits' => [
                    'alias'   => 'unique_hits',
                    'label'   => 'mautic.email.report.unique_hits_count',
                    'type'    => 'string',
                    'formula' => $channelUrlTrackables.'unique_hits',
                ],
                'hits_ratio' => [
                    'alias'   => 'hits_ratio',
                    'label'   => 'mautic.email.report.hits_ratio',
                    'type'    => 'string',
                    'formula' => 'CONCAT(ROUND('.$channelUrlTrackables.'hits/('.$prefix.'sent_count)*100, 1),\'%\')',
                ],
                'unique_ratio' => [
                    'alias'   => 'unique_ratio',
                    'label'   => 'mautic.email.report.unique_ratio',
                    'type'    => 'string',
                    'formula' => 'CONCAT(ROUND('.$channelUrlTrackables.'unique_hits/('.$prefix.'sent_count)*100, 1),\'%\')',
                ],
                'unsubscribed' => [
                    'alias'   => 'unsubscribed',
                    'label'   => 'mautic.email.report.unsubscribed',
                    'type'    => 'string',
                    'formula' => 'SUM(IF('.$doNotContact.'id IS NOT NULL AND dnc.reason='.DoNotContact::UNSUBSCRIBED.' , 1, 0))',
                ],
                'unsubscribed_ratio' => [
                    'alias'   => 'unsubscribed_ratio',
                    'label'   => 'mautic.email.report.unsubscribed_ratio',
                    'type'    => 'string',
                    'formula' => 'CONCAT(ROUND((SUM(IF('.$doNotContact.'id IS NOT NULL AND dnc.reason='.DoNotContact::UNSUBSCRIBED.' , 1, 0))/'.$prefix.'sent_count)*100, 1),\'%\')',
                ],
                'bounced' => [
                    'alias'   => 'bounced',
                    'label'   => 'mautic.email.report.bounced',
                    'type'    => 'string',
                    'formula' => 'SUM(IF('.$doNotContact.'id IS NOT NULL AND dnc.reason='.DoNotContact::BOUNCED.' , 1, 0))',
                ],
                'bounced_ratio' => [
                    'alias'   => 'bounced_ratio',
                    'label'   => 'mautic.email.report.bounced_ratio',
                    'type'    => 'string',
                    'formula' => 'CONCAT(ROUND((SUM(IF('.$doNotContact.'id IS NOT NULL AND dnc.reason='.DoNotContact::BOUNCED.' , 1, 0))/'.$prefix.'sent_count)*100, 1),\'%\')',
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
                    'label'          => 'mautic.email.report.variant_start_date',
                    'type'           => 'datetime',
                    'groupByFormula' => 'DATE('.$prefix.'variant_start_date)',
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
            $columns = array_merge(
                $columns,
                $event->getStandardColumns($prefix, [], 'mautic_email_action'),
                $event->getCategoryColumns(),
                $event->getCampaignByChannelColumns()
            );
            $data = [
                'display_name' => 'mautic.email.emails',
                'columns'      => $columns,
            ];
            $event->addTable('emails', $data);
            $context = 'emails';
            $event->addGraph($context, 'pie', 'mautic.email.graph.pie.read.ingored.unsubscribed.bounced');

            if ($event->checkContext('email.stats')) {
                // Ratios are not applicable for individual stats
                unset($columns['read_ratio'], $columns['unsubscribed_ratio'], $columns['bounced_ratio'], $columns['hits_ratio'], $columns['unique_ratio']);

                // Email counts are not applicable for individual stats
                unset($columns[$prefix.'read_count'], $columns[$prefix.'variant_sent_count'], $columns[$prefix.'variant_read_count']);

                // Prevent null DNC records from filtering the results
                $columns['unsubscribed']['type']    = 'bool';
                $columns['unsubscribed']['formula'] = 'IF(dnc.id IS NOT NULL AND dnc.reason='.DoNotContact::UNSUBSCRIBED.', 1, 0)';

                $columns['bounced']['type']    = 'bool';
                $columns['bounced']['formula'] = 'IF(dnc.id IS NOT NULL AND dnc.reason='.DoNotContact::BOUNCED.', 1, 0)';

                // clicked column for individual stats
                $columns['is_hit'] = [
                    'alias'   => 'is_hit',
                    'label'   => 'mautic.email.report.is_hit',
                    'type'    => 'bool',
                    'formula' => 'IF('.$channelUrlTrackables.'hits is NULL, 0, 1)',
                ];

                // time between sent and read
                $columns['read_delay'] = [
                    'alias'   => 'read_delay',
                    'label'   => 'mautic.email.report.read.delay',
                    'type'    => 'string',
                    'formula' => 'IF(es.date_read IS NOT NULL, TIMEDIFF(es.date_read, es.date_sent), \'-\')',
                ];

                $statPrefix  = 'es.';
                $statColumns = [
                    $statPrefix.'email_address' => [
                        'label' => 'mautic.email.report.stat.email_address',
                        'type'  => 'email',
                    ],
                    $statPrefix.'date_sent' => [
                        'label'          => 'mautic.email.report.stat.date_sent',
                        'type'           => 'datetime',
                        'groupByFormula' => 'DATE('.$statPrefix.'date_sent)',
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
                        'label'          => 'mautic.email.report.stat.date_read',
                        'type'           => 'datetime',
                        'groupByFormula' => 'DATE('.$statPrefix.'date_read)',
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
                    'columns'      => array_merge(
                        $columns,
                        $statColumns,
                        $event->getLeadColumns(),
                        $event->getIpColumn()
                    ),
                ];
                $event->addTable('email.stats', $data, 'emails');

                // Register Graphs
                $context = 'email.stats';
                $event->addGraph($context, 'line', 'mautic.email.graph.line.stats');
                $event->addGraph($context, 'pie', 'mautic.email.graph.pie.ignored.read.failed');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.sent');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.read');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.read.percent');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.unsubscribed');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.bounced');
                $event->addGraph($context, 'table', 'mautic.email.table.most.emails.failed');
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
        $context    = $event->getContext();
        $qb         = $event->getQueryBuilder();
        $hasGroupBy = $event->hasGroupBy();

        // channel_url_trackables subquery
        $qbcut        = $this->db->createQueryBuilder();
        $clickColumns = ['hits', 'unique_hits', 'hits_ratio', 'unique_ratio', 'is_hit'];
        $dncColumns   = ['unsubscribed', 'unsubscribed_ratio'];

        switch ($context) {
            case 'emails':
                $qb->from(MAUTIC_TABLE_PREFIX.'emails', 'e')
                    ->leftJoin('e', MAUTIC_TABLE_PREFIX.'emails', 'vp', 'vp.id = e.variant_parent_id');

                $event->addCategoryLeftJoin($qb, 'e');

                if (!$hasGroupBy) {
                    $qb->groupBy('e.id');
                }
                if ($event->hasColumn($clickColumns) || $event->hasFilter($clickColumns)) {
                    $qbcut->select(
                        'COUNT(cut2.channel_id) AS trackable_count, SUM(cut2.hits) AS hits',
                        'SUM(cut2.unique_hits) AS unique_hits',
                        'cut2.channel_id'
                    )
                        ->from(MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut2')
                        ->where('cut2.channel = \'email\'')
                        ->groupBy('cut2.channel_id');
                    $qb->leftJoin('e', sprintf('(%s)', $qbcut->getSQL()), 'cut', 'e.id = cut.channel_id');
                }

                if ($event->hasColumn($dncColumns) || $event->hasFilter($dncColumns)) {
                    $qb->leftJoin(
                        'e',
                        MAUTIC_TABLE_PREFIX.'lead_donotcontact',
                        'dnc',
                        'e.id = dnc.channel_id AND dnc.channel=\'email\''
                    );
                }

                break;
            case 'email.stats':
                $qb->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
                    ->leftJoin('es', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = es.email_id')
                    ->leftJoin('e', MAUTIC_TABLE_PREFIX.'emails', 'vp', 'vp.id = e.variant_parent_id');

                $event->addCategoryLeftJoin($qb, 'e')
                    ->addLeadLeftJoin($qb, 'es')
                    ->addIpAddressLeftJoin($qb, 'es')
                    ->applyDateFilters($qb, 'date_sent', 'es');

                if ($event->hasColumn($clickColumns) || $event->hasFilter($clickColumns)) {
                    $qbcut->select(
                        'COUNT(ph.id) AS hits',
                        'COUNT(DISTINCT(ph.redirect_id)) AS unique_hits',
                        'cut2.channel_id',
                        'ph.lead_id'
                    )
                        ->from(MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut2')
                        ->join(
                            'cut2',
                            MAUTIC_TABLE_PREFIX.'page_hits',
                            'ph',
                            'cut2.redirect_id = ph.redirect_id AND cut2.channel_id = ph.source_id'
                        )
                        ->where('cut2.channel = \'email\' AND ph.source = \'email\'')
                        ->groupBy('cut2.channel_id, ph.lead_id');
                    $qb->leftJoin(
                        'e',
                        sprintf('(%s)', $qbcut->getSQL()),
                        'cut',
                        'e.id = cut.channel_id AND es.lead_id = cut.lead_id'
                    );
                }

                if ($event->hasColumn($dncColumns) || $event->hasFilter($dncColumns)) {
                    $qb->leftJoin(
                        'e',
                        MAUTIC_TABLE_PREFIX.'lead_donotcontact',
                        'dnc',
                        'e.id = dnc.channel_id AND dnc.channel=\'email\' AND es.lead_id = dnc.lead_id'
                    );
                }

                $event->addCampaignByChannelJoin($qb, 'e', 'email');

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
        $graphs = $event->getRequestedGraphs();

        if (!$event->checkContext('email.stats') || ($event->checkContext('emails') && !in_array('mautic.email.graph.pie.read.ingored.unsubscribed.bounced', $graphs))) {
            return;
        }

        $qb       = $event->getQueryBuilder();
        $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');
        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;
            $chartQuery   = clone $options['chartQuery'];
            $origQuery    = clone $queryBuilder;
            // just limit date for contacts emails
            if ($event->checkContext('email.stats')) {
                $chartQuery->applyDateFilters($queryBuilder, 'date_sent', 'es');
            }

            switch ($g) {
                case 'mautic.email.graph.line.stats':
                    $chart     = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $sendQuery = clone $queryBuilder;
                    $readQuery = clone $origQuery;
                    $readQuery->andWhere($qb->expr()->isNotNull('date_read'));
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
                    $chart->setDataset(
                        $options['translator']->trans('mautic.email.ignored.emails'),
                        $counts['ignored']
                    );
                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-flag-checkered',
                        ]
                    );
                    break;

                case 'mautic.email.graph.pie.read.ingored.unsubscribed.bounced':
                    $queryBuilder->select(
                        'SUM(DISTINCT e.sent_count) as sent_count, SUM(DISTINCT e.read_count) as read_count, count(CASE WHEN dnc.id  and dnc.reason = '.DoNotContact::UNSUBSCRIBED.' THEN 1 ELSE null END) as unsubscribed, count(CASE WHEN dnc.id  and dnc.reason = '.DoNotContact::BOUNCED.' THEN 1 ELSE null END) as bounced'
                    );
                    $queryBuilder->resetQueryPart('groupBy');
                    $counts = $queryBuilder->execute()->fetch();
                    $chart  = new PieChart();
                    $chart->setDataset($options['translator']->trans('mautic.email.stat.read'), $counts['read_count']);
                    $chart->setDataset(
                        $options['translator']->trans('mautic.email.graph.pie.ignored.read.failed.ignored'),
                        ($counts['sent_count'] - $counts['read_count'])
                    );
                    $chart->setDataset(
                        $options['translator']->trans('mautic.email.unsubscribed'),
                        $counts['unsubscribed']
                    );
                    $chart->setDataset($options['translator']->trans('mautic.email.bounced'), $counts['bounced']);

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
                    $queryBuilder->select('e.id, e.subject as title, SUM(DISTINCT e. sent_count) as sent')
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
                    $queryBuilder->select('e.id, e.subject as title, SUM(DISTINCT e. read_count) as opens')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('opens', 'DESC');
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
                    $queryBuilder->select(
                        'e.id, e.subject as title, count(CASE WHEN es.is_failed THEN 1 ELSE null END) as failed'
                    )
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

                case 'mautic.email.table.most.emails.unsubscribed':
                    $queryBuilder->select(
                        'e.id, e.subject as title, count(CASE WHEN dnc.id  and dnc.reason = '.DoNotContact::UNSUBSCRIBED.' THEN 1 ELSE null END) as unsubscribed'
                    )
                        ->having(
                            'count(CASE WHEN dnc.id and dnc.reason = '.DoNotContact::UNSUBSCRIBED.' THEN 1 ELSE null END) > 0'
                        )
                        ->groupBy('e.id, e.subject')
                        ->orderBy('unsubscribed', 'DESC');
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

                case 'mautic.email.table.most.emails.bounced':
                    $queryBuilder->select(
                        'e.id, e.subject as title, count(CASE WHEN dnc.id  and dnc.reason = '.DoNotContact::BOUNCED.' THEN 1 ELSE null END) as bounced'
                    )
                        ->having(
                            'count(CASE WHEN dnc.id and dnc.reason = '.DoNotContact::BOUNCED.' THEN 1 ELSE null END) > 0'
                        )
                        ->groupBy('e.id, e.subject')
                        ->orderBy('bounced', 'DESC');
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
