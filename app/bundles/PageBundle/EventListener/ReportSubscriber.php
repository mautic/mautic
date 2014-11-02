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
            
            $hitRepo = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');

            $data = $hitRepo->prepareHitsGraphDataBefore($amount, $unit);

            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'page_hits', 'ph');
            $queryBuilder->leftJoin('ph', MAUTIC_TABLE_PREFIX . 'pages', 'p', 'p.id = ph.page_id');
            $queryBuilder->select('ph.page_id as page, ph.date_hit as dateHit');
            $event->buildWhere($queryBuilder);
            $queryBuilder->andwhere($queryBuilder->expr()->gte('ph.date_hit', ':date'))
                ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
            $hits = $queryBuilder->execute()->fetchAll();

            $timeStats = $hitRepo->prepareHitsGraphDataAfter($data, $hits, $unit);
            $timeStats['name'] = 'mautic.page.graph.line.hits';

            $event->setGraph('line', $timeStats);
        }
    }
}
