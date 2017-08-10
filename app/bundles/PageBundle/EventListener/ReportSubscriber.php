<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
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
     * {@inheritdoc}
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
        if ($event->checkContext(['pages', 'page.hits'])) {
            $prefix            = 'p.';
            $translationPrefix = 'tp.';
            $variantPrefix     = 'vp.';

            $columns = [
                $prefix.'title' => [
                    'label' => 'mautic.core.title',
                    'type'  => 'string',
                ],
                $prefix.'alias' => [
                    'label' => 'mautic.core.alias',
                    'type'  => 'string',
                ],
                $prefix.'revision' => [
                    'label' => 'mautic.page.report.revision',
                    'type'  => 'string',
                ],
                $prefix.'hits' => [
                    'label' => 'mautic.page.field.hits',
                    'type'  => 'int',
                ],
                $prefix.'unique_hits' => [
                    'label' => 'mautic.page.field.unique_hits',
                    'type'  => 'int',
                ],
                $translationPrefix.'id' => [
                    'label' => 'mautic.page.report.translation_parent_id',
                    'type'  => 'int',
                ],
                $translationPrefix.'title' => [
                    'label' => 'mautic.page.report.translation_parent_title',
                    'type'  => 'string',
                ],
                $variantPrefix.'id' => [
                    'label' => 'mautic.page.report.variant_parent_id',
                    'type'  => 'string',
                ],
                $variantPrefix.'title' => [
                    'label' => 'mautic.page.report.variant_parent_title',
                    'type'  => 'string',
                ],
                $prefix.'lang' => [
                    'label' => 'mautic.core.language',
                    'type'  => 'string',
                ],
                $prefix.'variant_start_date' => [
                    'label'          => 'mautic.page.report.variant_start_date',
                    'type'           => 'datetime',
                    'groupByFormula' => 'DATE('.$prefix.'variant_start_date)',
                ],
                $prefix.'variant_hits' => [
                    'label' => 'mautic.page.report.variant_hits',
                    'type'  => 'int',
                ],
            ];
            $columns = array_merge(
                $columns,
                $event->getStandardColumns('p.', ['name', 'description'], 'mautic_page_action'),
                $event->getCategoryColumns(),
                $event->getCampaignByChannelColumns()
            );
            $data = [
                'display_name' => 'mautic.page.pages',
                'columns'      => $columns,
            ];
            $event->addTable('pages', $data);

            if ($event->checkContext('page.hits')) {
                $hitPrefix   = 'ph.';
                $redirectHit = 'r.';
                $hitColumns  = [
                    $hitPrefix.'date_hit' => [
                        'label'          => 'mautic.page.report.hits.date_hit',
                        'type'           => 'datetime',
                        'groupByFormula' => 'DATE('.$hitPrefix.'date_hit)',
                    ],
                    $hitPrefix.'date_left' => [
                        'label'          => 'mautic.page.report.hits.date_left',
                        'type'           => 'datetime',
                        'groupByFormula' => 'DATE('.$hitPrefix.'date_left)',
                    ],
                    $hitPrefix.'country' => [
                        'label' => 'mautic.page.report.hits.country',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'region' => [
                        'label' => 'mautic.page.report.hits.region',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'city' => [
                        'label' => 'mautic.page.report.hits.city',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'isp' => [
                        'label' => 'mautic.page.report.hits.isp',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'organization' => [
                        'label' => 'mautic.page.report.hits.organization',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'code' => [
                        'label' => 'mautic.page.report.hits.code',
                        'type'  => 'int',
                    ],
                    $hitPrefix.'referer' => [
                        'label' => 'mautic.page.report.hits.referer',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'url' => [
                        'label' => 'mautic.page.report.hits.url',
                        'type'  => 'url',
                    ],
                    $hitPrefix.'url_title' => [
                        'label' => 'mautic.page.report.hits.url_title',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'user_agent' => [
                        'label' => 'mautic.page.report.hits.user_agent',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'remote_host' => [
                        'label' => 'mautic.page.report.hits.remote_host',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'browser_languages' => [
                        'label' => 'mautic.page.report.hits.browser_languages',
                        'type'  => 'array',
                    ],
                    $hitPrefix.'source' => [
                        'label' => 'mautic.report.field.source',
                        'type'  => 'string',
                    ],
                    $hitPrefix.'source_id' => [
                        'label' => 'mautic.report.field.source_id',
                        'type'  => 'int',
                    ],
                    $redirectHit.'url' => [
                        'label' => 'mautic.page.report.hits.redirect_url',
                        'type'  => 'url',
                    ],
                    $redirectHit.'hits' => [
                        'label' => 'mautic.page.report.hits.redirect_hit_count',
                        'type'  => 'int',
                    ],
                    $redirectHit.'unique_hits' => [
                        'label' => 'mautic.page.report.hits.redirect_unique_hits',
                        'type'  => 'string',
                    ],
                    'ds.device' => [
                        'label' => 'mautic.lead.device',
                        'type'  => 'string',
                    ],
                    'ds.device_brand' => [
                        'label' => 'mautic.lead.device_brand',
                        'type'  => 'string',
                    ],
                    'ds.device_model' => [
                        'label' => 'mautic.lead.device_model',
                        'type'  => 'string',
                    ],
                    'ds.device_os_name' => [
                        'label' => 'mautic.lead.device_os_name',
                        'type'  => 'string',
                    ],
                    'ds.device_os_shortname' => [
                        'label' => 'mautic.lead.device_os_shortname',
                        'type'  => 'string',
                    ],
                    'ds.device_os_version' => [
                        'label' => 'mautic.lead.device_os_version',
                        'type'  => 'string',
                    ],
                    'ds.device_os_platform' => [
                        'label' => 'mautic.lead.device_os_platform',
                        'type'  => 'string',
                    ],

                ];
                $data = [
                    'display_name' => 'mautic.page.hits',
                    'columns'      => array_merge($columns, $hitColumns, $event->getLeadColumns(), $event->getIpColumn()),
                ];
                $event->addTable('page.hits', $data, 'pages');

                // Register graphs
                $context = 'page.hits';
                $event->addGraph($context, 'line', 'mautic.page.graph.line.hits');
                $event->addGraph($context, 'line', 'mautic.page.graph.line.time.on.site');
                $event->addGraph($context, 'pie', 'mautic.page.graph.pie.time.on.site', ['translate' => false]);
                $event->addGraph($context, 'pie', 'mautic.page.graph.pie.new.vs.returning');
                $event->addGraph($context, 'pie', 'mautic.page.graph.pie.devices');
                $event->addGraph($context, 'pie', 'mautic.page.graph.pie.languages', ['translate' => false]);
                $event->addGraph($context, 'table', 'mautic.page.table.referrers');
                $event->addGraph($context, 'table', 'mautic.page.table.most.visited');
                $event->addGraph($context, 'table', 'mautic.page.table.most.visited.unique');
            }
        }
        if ($event->checkContext(['video.hits'])) {
            $hitPrefix  = 'vh.';
            $hitColumns = [
                $hitPrefix.'date_hit' => [
                    'label'          => 'mautic.page.report.hits.date_hit',
                    'type'           => 'datetime',
                    'groupByFormula' => 'DATE('.$hitPrefix.'date_hit)',
                ],
                $hitPrefix.'country' => [
                    'label' => 'mautic.page.report.hits.country',
                    'type'  => 'string',
                ],
                $hitPrefix.'region' => [
                    'label' => 'mautic.page.report.hits.region',
                    'type'  => 'string',
                ],
                $hitPrefix.'city' => [
                    'label' => 'mautic.page.report.hits.city',
                    'type'  => 'string',
                ],
                $hitPrefix.'isp' => [
                    'label' => 'mautic.page.report.hits.isp',
                    'type'  => 'string',
                ],
                $hitPrefix.'organization' => [
                    'label' => 'mautic.page.report.hits.organization',
                    'type'  => 'string',
                ],
                $hitPrefix.'code' => [
                    'label' => 'mautic.page.report.hits.code',
                    'type'  => 'int',
                ],
                $hitPrefix.'referer' => [
                    'label' => 'mautic.page.report.hits.referer',
                    'type'  => 'string',
                ],
                $hitPrefix.'url' => [
                    'label' => 'mautic.page.report.hits.url',
                    'type'  => 'url',
                ],
                $hitPrefix.'url_title' => [
                    'label' => 'mautic.page.report.hits.url_title',
                    'type'  => 'string',
                ],
                $hitPrefix.'user_agent' => [
                    'label' => 'mautic.page.report.hits.user_agent',
                    'type'  => 'string',
                ],
                $hitPrefix.'remote_host' => [
                    'label' => 'mautic.page.report.hits.remote_host',
                    'type'  => 'string',
                ],
                $hitPrefix.'browser_languages' => [
                    'label' => 'mautic.page.report.hits.browser_languages',
                    'type'  => 'array',
                ],
                $hitPrefix.'channel' => [
                    'label' => 'mautic.report.field.source',
                    'type'  => 'string',
                ],
                $hitPrefix.'channel_id' => [
                    'label' => 'mautic.report.field.source_id',
                    'type'  => 'int',
                ],
                'time_watched' => [
                    'label'   => 'mautic.page.report.hits.time_watched',
                    'type'    => 'string',
                    'formula' => 'SEC_TO_TIME('.$hitPrefix.'time_watched)',
                ],
                'duration' => [
                    'label'   => 'mautic.page.report.hits.duration',
                    'type'    => 'string',
                    'formula' => 'SEC_TO_TIME('.$hitPrefix.'duration)',
                ],
            ];

            $data = [
                'display_name' => 'mautic.video.hits',
                'columns'      => array_merge($hitColumns, $event->getLeadColumns(), $event->getIpColumn()),
            ];
            $event->addTable('video.hits', $data, 'videos');
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
            case 'pages':
                $qb->from(MAUTIC_TABLE_PREFIX.'pages', 'p')
                    ->leftJoin('p', MAUTIC_TABLE_PREFIX.'pages', 'tp', 'p.id = tp.id')
                    ->leftJoin('p', MAUTIC_TABLE_PREFIX.'pages', 'vp', 'p.id = vp.id');
                $event->addCategoryLeftJoin($qb, 'p');
                break;
            case 'page.hits':
                $event->applyDateFilters($qb, 'date_hit', 'ph');

                $qb->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
                    ->leftJoin('ph', MAUTIC_TABLE_PREFIX.'pages', 'p', 'ph.page_id = p.id')
                    ->leftJoin('p', MAUTIC_TABLE_PREFIX.'pages', 'tp', 'p.id = tp.id')
                    ->leftJoin('p', MAUTIC_TABLE_PREFIX.'pages', 'vp', 'p.id = vp.id')
                    ->leftJoin('ph', MAUTIC_TABLE_PREFIX.'page_redirects', 'r', 'r.id = ph.redirect_id')
                    ->leftJoin('ph', MAUTIC_TABLE_PREFIX.'lead_devices', 'ds', 'ds.id = ph.device_id');

                $event->addIpAddressLeftJoin($qb, 'ph');
                $event->addCategoryLeftJoin($qb, 'p');
                $event->addLeadLeftJoin($qb, 'ph');
                $event->addCampaignByChannelJoin($qb, 'p', 'page');
                break;
            case 'video.hits':
                $event->applyDateFilters($qb, 'date_hit', 'vh');

                $qb->from(MAUTIC_TABLE_PREFIX.'video_hits', 'vh');

                $event->addIpAddressLeftJoin($qb, 'vh');
                $event->addLeadLeftJoin($qb, 'vh');
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
        if (!$event->checkContext('page.hits')) {
            return;
        }

        $graphs  = $event->getRequestedGraphs();
        $qb      = $event->getQueryBuilder();
        $hitRepo = $this->em->getRepository('MauticPageBundle:Hit');

        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;

            /** @var ChartQuery $chartQuery */
            $chartQuery = clone $options['chartQuery'];
            $chartQuery->applyDateFilters($queryBuilder, 'date_hit', 'ph');

            switch ($g) {
                case 'mautic.page.graph.line.hits':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_hit', 'ph');
                    $hits = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans($g), $hits);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;

                case 'mautic.page.graph.line.time.on.site':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $queryBuilder->select('TIMESTAMPDIFF(SECOND, ph.date_hit, ph.date_left) as data, ph.date_hit as date');
                    $queryBuilder->andWhere($qb->expr()->isNotNull('ph.date_left'));

                    $hits = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans($g), $hits);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;

                case 'mautic.page.graph.pie.time.on.site':
                    $timesOnSite = $hitRepo->getDwellTimeLabels();
                    $chart       = new PieChart();

                    foreach ($timesOnSite as $time) {
                        $q = clone $queryBuilder;
                        $chartQuery->modifyCountDateDiffQuery($q, 'date_hit', 'date_left', $time['from'], $time['till'], 'ph');
                        $data = $chartQuery->fetchCountDateDiff($q);
                        $chart->setDataset($time['label'], $data);
                    }

                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-clock-o',
                        ]
                    );
                    break;

                case 'mautic.page.graph.pie.new.vs.returning':
                    $chart   = new PieChart();
                    $allQ    = clone $queryBuilder;
                    $uniqueQ = clone $queryBuilder;
                    $chartQuery->modifyCountQuery($allQ, 'date_hit', [], 'ph');
                    $chartQuery->modifyCountQuery($uniqueQ, 'date_hit', ['getUnique' => true, 'selectAlso' => ['ph.page_id']], 'ph');
                    $all       = $chartQuery->fetchCount($allQ);
                    $unique    = $chartQuery->fetchCount($uniqueQ);
                    $returning = $all - $unique;
                    $chart->setDataset($this->translator->trans('mautic.page.unique'), $unique);
                    $chart->setDataset($this->translator->trans('mautic.page.graph.pie.new.vs.returning.returning'), $returning);

                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-bookmark-o',
                        ]
                    );
                    break;

                case 'mautic.page.graph.pie.languages':
                    $queryBuilder->select('ph.page_language, COUNT(distinct(ph.id)) as the_count')
                        ->groupBy('ph.page_language')
                        ->andWhere($qb->expr()->isNotNull('ph.page_language'));
                    $data  = $queryBuilder->execute()->fetchAll();
                    $chart = new PieChart();

                    foreach ($data as $lang) {
                        $chart->setDataset($lang['page_language'], $lang['the_count']);
                    }

                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-globe',
                        ]
                    );
                    break;
                case 'mautic.page.graph.pie.devices':
                    $queryBuilder->select('ds.device, COUNT(distinct(ph.id)) as the_count')
                        ->groupBy('ds.device');
                    $data  = $queryBuilder->execute()->fetchAll();
                    $chart = new PieChart();

                    foreach ($data as $device) {
                        $label = substr(empty($device['device']) ? $this->translator->trans('mautic.core.no.info') : $device['device'], 0, 12);
                        $chart->setDataset($label, $device['the_count']);
                    }

                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-globe',
                        ]
                    );
                    break;
                case 'mautic.page.table.referrers':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $hitRepo->getReferers($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-sign-in';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.page.table.most.visited':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $hitRepo->getMostVisited($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-eye';
                    $graphData['link']      = 'mautic_page_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.page.table.most.visited.unique':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $hitRepo->getMostVisited($queryBuilder, $limit, $offset, 'p.unique_hits', 'sessions');
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-eye';
                    $graphData['link']      = 'mautic_page_action';
                    $event->setGraph($g, $graphData);
                    break;
            }

            unset($queryBuilder);
        }
    }
}
