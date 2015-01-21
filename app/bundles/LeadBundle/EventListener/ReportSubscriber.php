<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
        $prefix     = 'l.';
        $userPrefix = 'u.';
        $columns    = array(
            $prefix . 'date_identified' => array(
                'label' => 'mautic.lead.report.date_identified',
                'type'  => 'datetime'
            ),
            $prefix . 'points'          => array(
                'label' => 'mautic.lead.report.points',
                'type'  => 'int'
            ),
            $userPrefix . 'id'          => array(
                'label' => 'mautic.lead.report.owner_id',
                'type'  => 'int'
            ),
            $userPrefix . 'firstname'   => array(
                'label' => 'mautic.lead.report.owner_firstname',
                'type'  => 'string'
            ),
            $userPrefix . 'lastname'    => array(
                'label' => 'mautic.lead.report.owner_lastname',
                'type'  => 'string'
            )
        );

        /** @var \Mautic\LeadBundle\Model\FieldModel $model */
        $model        = $this->factory->getModel('lead.field');
        $leadFields   = $model->getEntities();
        $fieldColumns = array();
        foreach ($leadFields as $f) {
            switch ($f->getType()) {
                case 'boolean':
                    $type = 'bool';
                    break;
                case 'date':
                case 'datetime':
                case 'time':
                    $type = 'datetime';
                    break;
                case 'url':
                    $type = 'url';
                    break;
                case 'email':
                    $type = 'email';
                    break;
                default:
                    $type = 'string';
                    break;
            }
            $fieldColumns[$prefix . $f->getAlias()] = array(
                'label' => $f->getLabel(),
                'type'  => $type
            );
        }
        $data = array(
            'display_name' => 'mautic.lead.lead.report.table',
            'columns'      => array_merge($columns, $fieldColumns)
        );
        $event->addTable('leads', $data);

        $pointPrefix = 'p.';
        $pointColumns    = array(
            $pointPrefix . 'type' => array(
                'label' => 'mautic.lead.report.points.type',
                'type'  => 'string'
            ),
            $pointPrefix . 'event_name' => array(
                'label' => 'mautic.lead.report.points.event_name',
                'type'  => 'string'
            ),
            $pointPrefix . 'action_name' => array(
                'label' => 'mautic.lead.report.points.action_name',
                'type'  => 'string'
            ),
            $pointPrefix . 'delta' => array(
                'label' => 'mautic.lead.report.points.delta',
                'type'  => 'int'
            ),
            $pointPrefix . 'date_added' => array(
                'label' => 'mautic.lead.report.points.date_added',
                'type'  => 'datetime'
            )
        );
        $data = array(
            'display_name' => 'mautic.lead.report.points.table',
            'columns'      => array_merge($columns, $pointColumns, $event->getIpColumn())
        );
        $event->addTable('lead.pointlog', $data);

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
        if ($context == 'leads') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'leads', 'l');
            $qb->leftJoin('l', MAUTIC_TABLE_PREFIX . 'users', 'u', 'u.id = l.owner_id');

            $event->setQueryBuilder($qb);
        } elseif ($context == 'lead.pointlog') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'lead_points_change_log', 'p')
                ->leftJoin('p', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.id = p.lead_id');
            $event->addIpAddressLeftJoin($qb, 'p');

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

            $timeStats = GraphHelper::prepareDatetimeLineGraphData($amount, $unit, array('points'));

            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'lead_points_change_log', 'lp');
            $queryBuilder->leftJoin('lp', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.id = lp.lead_id');
            $queryBuilder->select('lp.lead_id as lead, lp.date_added as dateAdded, lp.delta');
            $event->buildWhere($queryBuilder);
            $queryBuilder->andwhere($queryBuilder->expr()->gte('lp.date_added', ':date'))
                ->setParameter('date', $timeStats['fromDate']->format('Y-m-d H:i:s'));
            $points = $queryBuilder->execute()->fetchAll();

            $timeStats = GraphHelper::mergeLineGraphData($timeStats, $points, $unit, 0, 'dateAdded', 'delta');
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
            $items = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.lead.table.most.points';
            $graphData['iconClass'] = 'fa-asterisk';
            $graphData['link'] = 'mautic_lead_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.lead.table.top.countries') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('l.country as title, count(l.country) as quantity')
                ->groupBy('l.country')
                ->orderBy('quantity', 'DESC');
            $limit = 10;
            $offset = 0;

            $items = $pointLogRepo->getMostLeads($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.lead.table.top.countries';
            $graphData['iconClass'] = 'fa-globe';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.lead.table.top.cities') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('l.city as title, count(l.city) as quantity')
                ->groupBy('l.city')
                ->orderBy('quantity', 'DESC');
            $limit = 10;
            $offset = 0;

            $items = $pointLogRepo->getMostLeads($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.lead.table.top.cities';
            $graphData['iconClass'] = 'fa-university';
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
            $items = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.lead.table.top.events';
            $graphData['iconClass'] = 'fa-calendar';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.lead.table.top.actions') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('lp.action_name as title, count(lp.action_name) as actions')
                ->groupBy('lp.action_name')
                ->orderBy('actions', 'DESC');
            $limit = 10;
            $offset = 0;
            $items = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.lead.table.top.actions';
            $graphData['iconClass'] = 'fa-bolt';
            $event->setGraph('table', $graphData);
        }
    }
}
