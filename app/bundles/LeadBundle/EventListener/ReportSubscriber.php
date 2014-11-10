<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
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
        $metadataLead = $this->factory->getEntityManager()->getClassMetadata('Mautic\\LeadBundle\\Entity\\Lead');
        $metadataPoint = $this->factory->getEntityManager()->getClassMetadata('Mautic\\LeadBundle\\Entity\\PointsChangeLog');
        $leadFields = $metadataLead->getFieldNames();
        $pointFields = $metadataPoint->getFieldNames();

        // Unset point change log id
        unset($pointFields[0]);

        $columns  = array();

        foreach ($leadFields as $field) {
            $fieldData = $metadataLead->getFieldMapping($field);
            $columns['l.' . $fieldData['columnName']] = array('label' => $field, 'type' => $fieldData['type']);
        }

        foreach ($pointFields as $field) {
            $fieldData = $metadataPoint->getFieldMapping($field);
            $columns['lp.' . $fieldData['columnName']] = array('label' => $field, 'type' => $fieldData['type']);
        }

        $data = array(
            'display_name' => 'mautic.lead.lead.report.table',
            'columns'      => $columns
        );

        $event->addTable('leads', $data);
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
        // Context check, we only want to fire for Lead reports
        if ($event->getContext() != 'leads')
        {
            return;
        }

        $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

        $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'leads', 'l');
        $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX . 'lead_points_change_log', 'lp', 'l.id = lp.lead_id');

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
        // Context check, we only want to fire for Lead reports
        if ($report->getSource() != 'leads')
        {
            return;
        }

        $options = $event->getOptions();
        $pointLogRepo = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:PointsChangeLog');

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.lead.graph.line.points') {
            // Generate data for points line graph
            $unit = 'D';
            $amount = 30;

            if (isset($options['amount'])) {
                $amount = $options['amount'];
            }

            if (isset($options['unit'])) {
                $unit = $options['unit'];
            }

            $timeStats = GraphHelper::prepareLineGraphData($amount, $unit);

            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'lead_points_change_log', 'lp');
            $queryBuilder->leftJoin('lp', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.id = lp.lead_id');
            $queryBuilder->select('lp.lead_id as lead, lp.date_added as dateAdded, lp.delta');
            $event->buildWhere($queryBuilder);
            $queryBuilder->andwhere($queryBuilder->expr()->gte('lp.date_added', ':date'))
                ->setParameter('date', $timeStats['fromDate']->format('Y-m-d H:i:s'));
            $points = $queryBuilder->execute()->fetchAll();

            $timeStats = GraphHelper::mergeLineGraphData($timeStats, $points, $unit, 'dateAdded', 'delta');
            $timeStats['name'] = 'mautic.lead.graph.line.points';

            $event->setGraph('line', $timeStats);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.lead.table.most.points') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('l.id, l.email as title, sum(lp.delta) as points')
                ->groupBy('l.id')
                ->orderBy('points', 'DESC');
            $limit = 10;
            $offset = 0;
            $items = $pointLogRepo->getMost($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.lead.table.most.points';
            $graphData['iconClass'] = 'fa-asterisk';
            $graphData['link'] = 'mautic_lead_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.lead.table.top.events') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('lp.event_name as title, count(lp.event_name) as events')
                ->groupBy('lp.event_name')
                ->orderBy('events', 'DESC');
            $limit = 10;
            $offset = 0;
            $items = $pointLogRepo->getMost($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.lead.table.top.events';
            $graphData['iconClass'] = 'fa-asterisk';
            $graphData['link'] = 'mautic_lead_action';
            $event->setGraph('table', $graphData);
        }
    }
}
