<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber
 */
class ReportSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ReportEvents::REPORT_ON_BUILD    => array('onReportBuilder', 0),
            ReportEvents::REPORT_ON_GENERATE => array('onReportGenerate', 0),
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
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if ($event->checkContext(array('pages', 'page.hits'))) {
            $prefix            = 'p.';
            $translationPrefix = 'tp.';
            $variantPrefix     = 'vp.';

            $columns = array(
                $prefix . 'title'              => array(
                    'label' => 'mautic.core.title',
                    'type'  => 'string'
                ),
                $prefix . 'alias'              => array(
                    'label' => 'mautic.core.alias',
                    'type'  => 'string'
                ),
                $prefix . 'revision'           => array(
                    'label' => 'mautic.page.report.revision',
                    'type'  => 'string'
                ),
                $prefix . 'hits'               => array(
                    'label' => 'mautic.page.field.hits',
                    'type'  => 'int'
                ),
                $prefix . 'unique_hits'        => array(
                    'label' => 'mautic.page.field.unique_hits',
                    'type'  => 'int'
                ),
                $translationPrefix . 'id'      => array(
                    'label' => 'mautic.page.report.translation_parent_id',
                    'type'  => 'int'
                ),
                $translationPrefix . 'title'   => array(
                    'label' => 'mautic.page.report.translation_parent_title',
                    'type'  => 'string'
                ),
                $variantPrefix . 'id'          => array(
                    'label' => 'mautic.page.report.variant_parent_id',
                    'type'  => 'string'
                ),
                $variantPrefix . 'title'       => array(
                    'label' => 'mautic.page.report.variant_parent_title',
                    'type'  => 'string'
                ),
                $prefix . 'lang'               => array(
                    'label' => 'mautic.core.language',
                    'type'  => 'string'
                ),
                $prefix . 'variant_start_date' => array(
                    'label' => 'mautic.page.report.variant_start_date',
                    'type'  => 'datetime'
                ),
                $prefix . 'variant_hits'       => array(
                    'label' => 'mautic.page.report.variant_hits',
                    'type'  => 'int'
                )
            );
            $columns = array_merge($columns, $event->getStandardColumns('p.', array('name', 'description')), $event->getCategoryColumns());
            $data    = array(
                'display_name' => 'mautic.page.pages',
                'columns'      => $columns
            );
            $event->addTable('pages', $data);

            if ($event->checkContext('page.hits')) {
                $hitPrefix   = 'ph.';
                $redirectHit = 'r.';
                $emailPrefix = 'e.';
                $hitColumns  = array(
                    $hitPrefix . 'date_hit'          => array(
                        'label' => 'mautic.page.report.hits.date_hit',
                        'type'  => 'datetime'
                    ),
                    $hitPrefix . 'date_left'         => array(
                        'label' => 'mautic.page.report.hits.date_left',
                        'type'  => 'datetime'
                    ),
                    $hitPrefix . 'country'           => array(
                        'label' => 'mautic.page.report.hits.country',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'region'            => array(
                        'label' => 'mautic.page.report.hits.region',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'city'              => array(
                        'label' => 'mautic.page.report.hits.city',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'isp'               => array(
                        'label' => 'mautic.page.report.hits.isp',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'organization'      => array(
                        'label' => 'mautic.page.report.hits.organization',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'code'              => array(
                        'label' => 'mautic.page.report.hits.code',
                        'type'  => 'int'
                    ),
                    $hitPrefix . 'referer'           => array(
                        'label' => 'mautic.page.report.hits.referer',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'url'               => array(
                        'label' => 'mautic.page.report.hits.url',
                        'type'  => 'url'
                    ),
                    $hitPrefix . 'url_title'       => array(
                        'label' => 'mautic.page.report.hits.url_title',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'user_agent'        => array(
                        'label' => 'mautic.page.report.hits.user_agent',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'remote_host'       => array(
                        'label' => 'mautic.page.report.hits.remote_host',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'browser_languages' => array(
                        'label' => 'mautic.page.report.hits.browser_languages',
                        'type'  => 'array'
                    ),
                    $hitPrefix . 'source'            => array(
                        'label' => 'mautic.report.field.source',
                        'type'  => 'string'
                    ),
                    $hitPrefix . 'source_id'         => array(
                        'label' => 'mautic.report.field.source_id',
                        'type'  => 'int'
                    ),
                    $redirectHit . 'url'             => array(
                        'label' => 'mautic.page.report.hits.redirect_url',
                        'type'  => 'url'
                    ),
                    $redirectHit . 'hits'            => array(
                        'label' => 'mautic.page.report.hits.redirect_hit_count',
                        'type'  => 'int'
                    ),
                    $redirectHit . 'unique_hits'     => array(
                        'label' => 'mautic.page.report.hits.redirect_unique_hits',
                        'type'  => 'string'
                    ),
                    $emailPrefix . 'id'              => array(
                        'label' => 'mautic.page.report.hits.email_id',
                        'type'  => 'int'
                    ),
                    $emailPrefix . 'subject'         => array(
                        'label' => 'mautic.page.report.hits.email_subject',
                        'type'  => 'string'
                    )
                );
                $data        = array(
                    'display_name' => 'mautic.page.hits',
                    'columns'      => array_merge($columns, $hitColumns, $event->getLeadColumns(), $event->getIpColumn())
                );
                $event->addTable('page.hits', $data);

                // Register graphs
                $context = 'page.hits';
                $event->addGraph($context, 'line',  'mautic.page.graph.line.hits');
                $event->addGraph($context, 'line',  'mautic.page.graph.line.time.on.site');
                $event->addGraph($context, 'pie',   'mautic.page.graph.pie.time.on.site', array('translate' => false));
                $event->addGraph($context, 'pie',   'mautic.page.graph.pie.new.vs.returning');
                $event->addGraph($context, 'pie',   'mautic.page.graph.pie.languages', array('translate' => false));
                $event->addGraph($context, 'table', 'mautic.page.table.referrers');
                $event->addGraph($context, 'table', 'mautic.page.table.most.visited');
                $event->addGraph($context, 'table', 'mautic.page.table.most.visited.unique');
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
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        $context = $event->getContext();
        if ($context == 'pages') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'pages', 'p')
                ->leftJoin('p', MAUTIC_TABLE_PREFIX . 'pages', 'tp', 'p.id = tp.id')
                ->leftJoin('p', MAUTIC_TABLE_PREFIX . 'pages', 'vp', 'p.id = vp.id');
            $event->addCategoryLeftJoin($qb, 'p');

            $event->setQueryBuilder($qb);
        } elseif ($context == 'page.hits') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'page_hits', 'ph')
                ->leftJoin('ph', MAUTIC_TABLE_PREFIX . 'pages', 'p', 'ph.page_id = p.id')
                ->leftJoin('p', MAUTIC_TABLE_PREFIX . 'pages', 'tp', 'p.id = tp.id')
                ->leftJoin('p', MAUTIC_TABLE_PREFIX . 'pages', 'vp', 'p.id = vp.id')
                ->leftJoin('ph', MAUTIC_TABLE_PREFIX . 'emails', 'e', 'e.id = ph.email_id')
                ->leftJoin('ph', MAUTIC_TABLE_PREFIX . 'page_redirects', 'r', 'r.id = ph.redirect_id');

            $event->addIpAddressLeftJoin($qb, 'ph');
            $event->addCategoryLeftJoin($qb, 'p');
            $event->addLeadLeftJoin($qb, 'ph');

            $event->setQueryBuilder($qb);
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGraphEvent $event
     *
     * @return void
     */
    public function onReportGraphGenerate(ReportGraphEvent $event)
    {
        // Context check, we only want to fire for Lead reports
        if (!$event->checkContext('page.hits')) {
            return;
        }

        $graphs  = $event->getRequestedGraphs();
        $qb      = $event->getQueryBuilder();
        $hitRepo = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');

        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;

            switch ($g) {
                case 'mautic.page.graph.line.hits':
                    // Generate data for Downloads line graph
                    $unit   = 'D';
                    $amount = 30;

                    if (isset($options['amount'])) {
                        $amount = $options['amount'];
                    }

                    if (isset($options['unit'])) {
                        $unit = $options['unit'];
                    }

                    $data = GraphHelper::prepareDatetimeLineGraphData($amount, $unit, array('dateHit'));

                    $queryBuilder->select('ph.page_id as page, ph.date_hit as "dateHit"');
                    $queryBuilder->andwhere($queryBuilder->expr()->gte('ph.date_hit', ':date'))
                        ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
                    $hits = $queryBuilder->execute()->fetchAll();

                    $timeStats         = GraphHelper::mergeLineGraphData($data, $hits, $unit, 0, 'dateHit');
                    $timeStats['name'] = 'mautic.page.graph.line.hits';

                    $event->setGraph($g, $timeStats);
                    break;

                case 'mautic.page.graph.line.time.on.site':
                    // Generate data for Downloads line graph
                    $unit   = 'D';
                    $amount = 30;

                    if (isset($options['amount'])) {
                        $amount = $options['amount'];
                    }

                    if (isset($options['unit'])) {
                        $unit = $options['unit'];
                    }

                    $data = GraphHelper::prepareDatetimeLineGraphData($amount, $unit, array('dateHit'));

                    $queryBuilder->select('ph.page_id as page, ph.date_hit as "dateHit", ph.date_left as "dateLeft"');
                    $queryBuilder->andwhere($queryBuilder->expr()->gte('ph.date_hit', ':date'))
                        ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
                    $hits = $queryBuilder->execute()->fetchAll();

                    // Count time on site
                    foreach ($hits as $key => $hit) {
                        if ($hit['dateLeft']) {
                            $dateHit                      = new \DateTime($hit['dateHit']);
                            $dateLeft                     = new \DateTime($hit['dateLeft']);
                            $hits[$key]['timeOnSite']     = $dateLeft->getTimestamp() - $dateHit->getTimestamp();
                            $hits[$key]['timeOnSiteDate'] = $hit['dateHit'];
                        } else {
                            $hits[$key]['timeOnSite']     = 0;
                            $hits[$key]['timeOnSiteDate'] = $hit['dateHit'];
                        }
                        unset($hits[$key]['dateLeft']);
                    }

                    $timeStats         = GraphHelper::mergeLineGraphData($data, $hits, $unit, 0, 'dateHit', 'timeOnSite', true);
                    $timeStats['name'] = 'mautic.page.graph.line.time.on.site';

                    $event->setGraph($g, $timeStats);
                    break;

                case 'mautic.page.graph.pie.time.on.site':
                    $hitStats               = $hitRepo->getDwellTimes(array(), $queryBuilder);
                    $graphData              = array();
                    $graphData['data']      = $hitStats['timesOnSite'];
                    $graphData['name']      = 'mautic.page.graph.pie.time.on.site';
                    $graphData['iconClass'] = 'fa-clock-o';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.page.graph.pie.new.vs.returning':
                    if (!isset($hitstats)) {
                        $hitStats = $hitRepo->getDwellTimes(array(), $queryBuilder);
                    }
                    $graphData              = array();
                    $graphData['data']      = $hitStats['newVsReturning'];
                    $graphData['name']      = 'mautic.page.graph.pie.new.vs.returning';
                    $graphData['iconClass'] = 'fa-bookmark-o';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.page.graph.pie.languages':
                    if (!isset($hitstats)) {
                        $hitStats = $hitRepo->getDwellTimes(array(), $queryBuilder);
                    }
                    $graphData              = array();
                    $graphData['data']      = $hitStats['languages'];
                    $graphData['name']      = 'mautic.page.graph.pie.languages';
                    $graphData['iconClass'] = 'fa-globe';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.page.table.referrers':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $hitRepo->getReferers($queryBuilder, $limit, $offset);
                    $graphData              = array();
                    $graphData['data']      = $items;
                    $graphData['name']      = 'mautic.page.table.referrers';
                    $graphData['iconClass'] = 'fa-sign-in';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.page.table.most.visited':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $hitRepo->getMostVisited($queryBuilder, $limit, $offset);
                    $graphData              = array();
                    $graphData['data']      = $items;
                    $graphData['name']      = 'mautic.page.table.most.visited';
                    $graphData['iconClass'] = 'fa-eye';
                    $graphData['link']      = 'mautic_page_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.page.table.most.visited.unique':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $hitRepo->getMostVisited($queryBuilder, $limit, $offset, 'p.unique_hits', 'sessions');
                    $graphData              = array();
                    $graphData['data']      = $items;
                    $graphData['name']      = 'mautic.page.table.most.visited.unique';
                    $graphData['iconClass'] = 'fa-eye';
                    $graphData['link']      = 'mautic_page_action';
                    $event->setGraph($g, $graphData);
                    break;
            }

            unset($queryBuilder);
        }
    }
}
