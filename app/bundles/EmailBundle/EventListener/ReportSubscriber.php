<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

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
        $metadataEmail = $this->factory->getEntityManager()->getClassMetadata('Mautic\\EmailBundle\\Entity\\Email');
        $metadataStat = $this->factory->getEntityManager()->getClassMetadata('Mautic\\EmailBundle\\Entity\\Stat');
        $emailFields = $metadataEmail->getFieldNames();
        $statFields = $metadataStat->getFieldNames();

        // Unset stat id
        unset($statFields[0]);

        $columns  = array();

        foreach ($emailFields as $field) {
            $fieldData = $metadataEmail->getFieldMapping($field);
            $columns['a.' . $fieldData['columnName']] = array('label' => $field, 'type' => $fieldData['type']);
        }

        foreach ($statFields as $field) {
            $fieldData = $metadataStat->getFieldMapping($field);
            $columns['ad.' . $fieldData['columnName']] = array('label' => $field, 'type' => $fieldData['type']);
        }

        $data = array(
            'display_name' => 'mautic.email.email.report.table',
            'columns'      => $columns
        );

        $event->addTable('emails', $data);
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
        // Context check, we only want to fire for Email reports
        if ($event->getContext() != 'emails')
        {
            return;
        }

        $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

        $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'emails', 'e');
        $queryBuilder->leftJoin('e', MAUTIC_TABLE_PREFIX . 'email_stats', 'es', 'e.id = es.email_id');

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
        // Context check, we only want to fire for Email reports
        if ($report->getSource() != 'emails')
        {
            return;
        }

        $options = $event->getOptions();
        $statRepo = $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.email.graph.line.stats') {
            // Generate data for Stats line graph
            $unit = 'D';
            $amount = 30;

            if (isset($options['amount'])) {
                $amount = $options['amount'];
            }

            if (isset($options['unit'])) {
                $unit = $options['unit'];
            }

            $timeStats = GraphHelper::prepareLineGraphData($amount, $unit, array('sent', 'read', 'failed'));

            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'email_stats', 'es');
            $queryBuilder->leftJoin('es', MAUTIC_TABLE_PREFIX . 'emails', 'e', 'e.id = es.email_id');
            $queryBuilder->select('es.email_id as email, es.date_sent as dateSent, es.date_read as dateRead, is_failed');
            $event->buildWhere($queryBuilder);
            $queryBuilder->andwhere($queryBuilder->expr()->gte('es.date_sent', ':date'))
                ->setParameter('date', $timeStats['fromDate']->format('Y-m-d H:i:s'));
            $stats = $queryBuilder->execute()->fetchAll();

            $timeStats = GraphHelper::mergeLineGraphData($timeStats, $stats, $unit, 0, 'dateSent');
            $timeStats = GraphHelper::mergeLineGraphData($timeStats, $stats, $unit, 1, 'dateRead');
            $timeStats = GraphHelper::mergeLineGraphData($timeStats, $stats, $unit, 2, 'dateSent', 'is_failed');
            $timeStats['name'] = 'mautic.email.graph.line.stats';

            $event->setGraph('line', $timeStats);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.email.graph.pie.ignored.read.failed') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $items = $statRepo->getIgnoredReadFailed($queryBuilder);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.email.graph.pie.ignored.read.failed';
            $graphData['iconClass'] = 'fa-flag-checkered';
            $event->setGraph('pie', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.email.table.most.emails.sent') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('e.id, e.subject as title, count(es.id) as sent')
                ->groupBy('e.id')
                ->orderBy('sent', 'DESC');
            $limit = 10;
            $offset = 0;
            $items = $statRepo->getMostEmails($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.email.table.most.emails.sent';
            $graphData['iconClass'] = 'fa-paper-plane-o';
            $graphData['link'] = 'mautic_email_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.email.table.most.emails.read') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('e.id, e.subject as title, sum(es.is_read) as "read"')
                ->groupBy('e.id')
                ->orderBy('"read"', 'DESC');
            $limit = 10;
            $offset = 0;
            $items = $statRepo->getMostEmails($queryBuilder, $limit, $offset, 'e.id, e.subject as title, sum(es.is_read) as "read"');
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.email.table.most.emails.read';
            $graphData['iconClass'] = 'fa-eye';
            $graphData['link'] = 'mautic_email_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.email.table.most.emails.failed') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('e.id, e.subject as title, sum(es.is_failed) as failed')
                ->andWhere('es.is_failed > 0')
                ->groupBy('e.id')
                ->orderBy('failed', 'DESC');
            $limit = 10;
            $offset = 0;
            $items = $statRepo->getMostEmails($queryBuilder, $limit, $offset, 'e.id, e.subject as title, sum(es.is_read) as "read"');
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.email.table.most.emails.failed';
            $graphData['iconClass'] = 'fa-exclamation-triangle';
            $graphData['link'] = 'mautic_email_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.email.table.most.emails.read.percent') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $queryBuilder->select('e.id, e.subject as title, round(e.read_count / e.sent_count * 100) as ratio')
                ->groupBy('e.id')
                ->orderBy('ratio', 'DESC');
            $limit = 10;
            $offset = 0;
            $items = $statRepo->getMostEmails($queryBuilder, $limit, $offset, 'e.id, e.subject as title, sum(es.is_read) as "read"');
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.email.table.most.emails.read.percent';
            $graphData['iconClass'] = 'fa-tachometer';
            $graphData['link'] = 'mautic_email_action';
            $event->setGraph('table', $graphData);
        }
    }
}
