<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
 *
 * @package Mautic\PageBundle\EventListener
 */
class ReportSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
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
        $metadata = $this->factory->getEntityManager()->getClassMetadata('Mautic\\PageBundle\\Entity\\Page');
        $fields   = $metadata->getFieldNames();
        $columns  = array();

        foreach ($fields as $field) {
            $fieldData = $metadata->getFieldMapping($field);
            $columns['p.' . $fieldData['columnName']] = array('label' => $field, 'type' => $fieldData['type']);
        }

        $data = array(
            'display_name' => 'mautic.page.page.report.table',
            'columns'      => $columns
        );

        $event->addTable('pages', $data);
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
        // Context check, we only want to fire for Page reports
        if ($event->getContext() != 'pages')
        {
            return;
        }

        $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

        $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'pages', 'p');

        $event->setQueryBuilder($queryBuilder);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGeneratorEvent $event
     *
     * @return void
     */
    public function onReportGraphGenerate(ReportGraphEvent $event)
    {
        $report = $event->getReport();
        // Context check, we only want to fire for Asset reports
        if ($report->getSource() != 'pages')
        {
            return;
        }

        $options = $event->getOptions();
        $hitRepo = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.page.graph.line.hits') {
            // Generate data for Downloads line graph
            $unit = 'D';
            $amount = 30;

            if (isset($options['amount'])) {
                $amount = $options['amount'];
            }

            if (isset($options['unit'])) {
                $unit = $options['unit'];
            }

            $data = GraphHelper::prepareLineGraphData($amount, $unit, array('dateHit'));

            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'page_hits', 'ph');
            $queryBuilder->leftJoin('ph', MAUTIC_TABLE_PREFIX . 'pages', 'p', 'p.id = ph.page_id');
            $queryBuilder->select('ph.page_id as page, ph.date_hit as dateHit');
            $event->buildWhere($queryBuilder);
            $queryBuilder->andwhere($queryBuilder->expr()->gte('ph.date_hit', ':date'))
                ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
            $hits = $queryBuilder->execute()->fetchAll();

            $timeStats = GraphHelper::mergeLineGraphData($data, $hits, $unit, 0, 'dateHit');
            $timeStats['name'] = 'mautic.page.graph.line.hits';

            $event->setGraph('line', $timeStats);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.page.graph.line.time.on.site') {
            // Generate data for Downloads line graph
            $unit = 'D';
            $amount = 30;

            if (isset($options['amount'])) {
                $amount = $options['amount'];
            }

            if (isset($options['unit'])) {
                $unit = $options['unit'];
            }

            $data = GraphHelper::prepareLineGraphData($amount, $unit, array('dateHit'));

            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'page_hits', 'ph');
            $queryBuilder->leftJoin('ph', MAUTIC_TABLE_PREFIX . 'pages', 'p', 'p.id = ph.page_id');
            $queryBuilder->select('ph.page_id as page, ph.date_hit as dateHit, ph.date_left as dateLeft');
            $event->buildWhere($queryBuilder);
            $queryBuilder->andwhere($queryBuilder->expr()->gte('ph.date_hit', ':date'))
                ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
            $hits = $queryBuilder->execute()->fetchAll();

            // Count time on site
            foreach ($hits as $key => $hit) {
                if ($hit['dateLeft']) {
                    $dateHit = new \DateTime($hit['dateHit']);
                    $dateLeft = new \DateTime($hit['dateLeft']);
                    $hits[$key]['timeOnSite'] = $dateLeft->getTimestamp() - $dateHit->getTimestamp();
                    $hits[$key]['timeOnSiteDate'] = $hit['dateHit'];
                } else {
                    $hits[$key]['timeOnSite'] = 0;
                    $hits[$key]['timeOnSiteDate'] = $hit['dateHit'];
                }
                unset($hits[$key]['dateLeft']);
            }

            $timeStats = GraphHelper::mergeLineGraphData($data, $hits, $unit, 0, 'dateHit', 'timeOnSite', true);
            $timeStats['name'] = 'mautic.page.graph.line.time.on.site';

            $event->setGraph('line', $timeStats);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.page.graph.pie.time.on.site') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $hitStats = $hitRepo->getDwellTimes(null, null, $queryBuilder);
            $graphData = array();
            $graphData['data'] = $hitStats['timesOnSite'];
            $graphData['name'] = 'mautic.page.graph.pie.time.on.site';
            $graphData['iconClass'] = 'fa-clock-o';
            $event->setGraph('pie', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.page.graph.pie.new.vs.returning') {
            if (!isset($hitstats)) {
                $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
                $event->buildWhere($queryBuilder);
                $hitStats = $hitRepo->getDwellTimes(null, null, $queryBuilder);
            }
            $graphData = array();
            $graphData['data'] = $hitStats['newVsReturning'];
            $graphData['name'] = 'mautic.page.graph.pie.new.vs.returning';
            $graphData['iconClass'] = 'fa-bookmark-o';
            $event->setGraph('pie', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.page.graph.pie.languages') {
            if (!isset($hitstats)) {
                $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
                $event->buildWhere($queryBuilder);
                $hitStats = $hitRepo->getDwellTimes(null, null, $queryBuilder);
            }
            $graphData = array();
            $graphData['data'] = $hitStats['languages'];
            $graphData['name'] = 'mautic.page.graph.pie.languages';
            $graphData['iconClass'] = 'fa-globe';
            $event->setGraph('pie', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.page.table.referrers') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $limit = 10;
            $offset = 0;
            $items = $hitRepo->getReferers($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.page.table.referrers';
            $graphData['iconClass'] = 'fa-sign-in';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.page.table.most.visited') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $limit = 10;
            $offset = 0;
            $items = $hitRepo->getMostVisited($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.page.table.most.visited';
            $graphData['iconClass'] = 'fa-eye';
            $graphData['link'] = 'mautic_page_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.page.table.most.visited.unique') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $limit = 10;
            $offset = 0;
            $items = $hitRepo->getMostVisited($queryBuilder, $limit, $offset, 'p.unique_hits', 'sessions');
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.page.table.most.visited.unique';
            $graphData['iconClass'] = 'fa-eye';
            $graphData['link'] = 'mautic_page_action';
            $event->setGraph('table', $graphData);
        }
    }
}
