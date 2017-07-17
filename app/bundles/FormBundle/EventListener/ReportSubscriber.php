<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\Chart\LineChart;
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
        if ($event->checkContext(['forms', 'form.submissions'])) {
            // Forms
            $prefix  = 'f.';
            $columns = [
                $prefix.'alias' => [
                    'label' => 'mautic.core.alias',
                    'type'  => 'string',
                ],
            ];
            $columns = array_merge(
                $columns,
                $event->getStandardColumns($prefix, [], 'mautic_form_action'),
                $event->getCategoryColumns(),
                $event->getCampaignByChannelColumns()
            );
            $data = [
                'display_name' => 'mautic.form.forms',
                'columns'      => $columns,
            ];
            $event->addTable('forms', $data);
            if ($event->checkContext('form.submissions')) {
                // Form submissions
                $submissionPrefix  = 'fs.';
                $pagePrefix        = 'p.';
                $submissionColumns = [
                    $submissionPrefix.'date_submitted' => [
                        'label' => 'mautic.form.report.submit.date_submitted',
                        'type'  => 'datetime',
                    ],
                    $submissionPrefix.'referer' => [
                        'label' => 'mautic.core.referer',
                        'type'  => 'string',
                    ],
                    $pagePrefix.'id' => [
                        'label' => 'mautic.form.report.page_id',
                        'type'  => 'int',
                        'link'  => 'mautic_page_action',
                    ],
                    $pagePrefix.'title' => [
                        'label' => 'mautic.form.report.page_name',
                        'type'  => 'string',
                    ],
                ];
                $data = [
                    'display_name' => 'mautic.form.report.submission.table',
                    'columns'      => array_merge($submissionColumns, $columns, $event->getLeadColumns(), $event->getIpColumn()),
                ];
                $event->addTable('form.submissions', $data, 'forms');

                // Register graphs
                $context = 'form.submissions';
                $event->addGraph($context, 'line', 'mautic.form.graph.line.submissions');
                $event->addGraph($context, 'table', 'mautic.form.table.top.referrers');
                $event->addGraph($context, 'table', 'mautic.form.table.most.submitted');
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
            case 'forms':
                $qb->from(MAUTIC_TABLE_PREFIX.'forms', 'f');
                $event->addCategoryLeftJoin($qb, 'f');
                $event->addCampaignByChannelJoin($qb, 'f', 'form');
                break;
            case 'form.submissions':
                $event->applyDateFilters($qb, 'date_submitted', 'fs');

                $qb->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs')
                    ->leftJoin('fs', MAUTIC_TABLE_PREFIX.'forms', 'f', 'f.id = fs.form_id')
                    ->leftJoin('fs', MAUTIC_TABLE_PREFIX.'pages', 'p', 'p.id = fs.page_id');
                $event->addCategoryLeftJoin($qb, 'f');
                $event->addLeadLeftJoin($qb, 'fs');
                $event->addIpAddressLeftJoin($qb, 'fs');
                $event->addCampaignByChannelJoin($qb, 'f', 'form');
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
        if (!$event->checkContext('form.submissions')) {
            return;
        }

        $graphs         = $event->getRequestedGraphs();
        $qb             = $event->getQueryBuilder();
        $submissionRepo = $this->em->getRepository('MauticFormBundle:Submission');

        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;
            $chartQuery   = clone $options['chartQuery'];
            $chartQuery->applyDateFilters($queryBuilder, 'date_submitted', 'fs');

            switch ($g) {
                case 'mautic.form.graph.line.submissions':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_submitted', 'fs');
                    $hits = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans($g), $hits);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;
                    break;

                case 'mautic.form.table.top.referrers':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $submissionRepo->getTopReferrers($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-sign-in';
                    $graphData['link']      = 'mautic_form_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.form.table.most.submitted':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $submissionRepo->getMostSubmitted($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-check-square-o';
                    $graphData['link']      = 'mautic_form_action';
                    $event->setGraph($g, $graphData);
                    break;
            }
            unset($queryBuilder);
        }
    }
}
