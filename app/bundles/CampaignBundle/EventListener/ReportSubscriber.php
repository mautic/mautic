<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
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
        $context = 'campaign_lead_event_log';

        if ($event->checkContext($context)) {
            $prefix           = 'log.';
            $aliasPrefix      = 'log_';
            $campaignPrefix   = 'c.';
            $eventPrefix      = 'e.';
            $eventAliasPrefix = 'e_';
            $catPrefix        = 'cat.';
            $leadPrefix       = 'l.';

            $columns = [
                // Log columns
                $prefix.'date_triggered' => [
                    'label'          => 'mautic.report.campaign.log.date_triggered',
                    'type'           => 'datetime',
                    'alias'          => $aliasPrefix.'date_triggered',
                    'groupByFormula' => 'DATE('.$prefix.'date_triggered)',
                ],
                $prefix.'is_scheduled' => [
                    'label' => 'mautic.report.campaign.log.is_scheduled',
                    'type'  => 'boolean',
                    'alias' => $aliasPrefix.'is_scheduled',
                ],
                $prefix.'trigger_date' => [
                    'label'          => 'mautic.report.campaign.log.trigger_date',
                    'type'           => 'datetime',
                    'alias'          => $aliasPrefix.'trigger_date',
                    'groupByFormula' => 'DATE('.$prefix.'trigger_date)',
                ],
                $prefix.'system_triggered' => [
                    'label' => 'mautic.report.campaign.log.system_triggered',
                    'type'  => 'boolean',
                    'alias' => $aliasPrefix.'system_triggered',
                ],
                $prefix.'non_action_path_taken' => [
                    'label' => 'mautic.report.campaign.log.non_action_path_taken',
                    'type'  => 'boolean',
                    'alias' => $aliasPrefix.'non_action_path_taken',
                ],
                $prefix.'channel' => [
                    'label' => 'mautic.report.campaign.log.channel',
                    'type'  => 'string',
                    'alias' => $aliasPrefix.'channel',
                ],
                $prefix.'channel_id' => [
                    'label' => 'mautic.report.campaign.log.channel_id',
                    'type'  => 'int',
                    'alias' => $aliasPrefix.'channel_id',
                ],

                // Event columns
                $eventPrefix.'name' => [
                    'label' => 'mautic.report.campaign.event.name',
                    'type'  => 'string',
                    'alias' => $eventAliasPrefix.'name',
                ],
                $eventPrefix.'description' => [
                    'label' => 'mautic.report.campaign.event.description',
                    'type'  => 'string',
                    'alias' => $eventAliasPrefix.'description',
                ],
                $eventPrefix.'type' => [
                    'label' => 'mautic.report.campaign.event.type',
                    'type'  => 'string',
                    'alias' => $eventAliasPrefix.'type',
                ],
                $eventPrefix.'event_type' => [
                    'label' => 'mautic.report.campaign.event.event_type',
                    'type'  => 'string',
                    'alias' => $eventAliasPrefix.'event_type',
                ],
                $eventPrefix.'trigger_date' => [
                    'label'          => 'mautic.report.campaign.event.trigger_date',
                    'type'           => 'datetime',
                    'alias'          => $eventAliasPrefix.'trigger_date',
                    'groupByFormula' => 'DATE('.$eventPrefix.'trigger_date)',
                ],
                $eventPrefix.'trigger_mode' => [
                    'label' => 'mautic.report.campaign.event.trigger_mode',
                    'type'  => 'string',
                    'alias' => $eventAliasPrefix.'trigger_mode',
                ],
                $eventPrefix.'channel' => [
                    'label' => 'mautic.report.campaign.event.channel',
                    'type'  => 'string',
                    'alias' => $eventAliasPrefix.'channel',
                ],
                $eventPrefix.'channel_id' => [
                    'label' => 'mautic.report.campaign.event.channel_id',
                    'type'  => 'int',
                    'alias' => $eventAliasPrefix.'channel_id',
                ],
            ];

            $columns = array_merge(
                $columns,
                $event->getStandardColumns($campaignPrefix, [], 'mautic_campaign_action'),
                $event->getCategoryColumns($catPrefix),
                $event->getLeadColumns($leadPrefix),
                $event->getIpColumn(),
                $event->getChannelColumns()
            );

            $data = [
                'display_name' => 'mautic.campaign.events',
                'columns'      => $columns,
            ];
            $event->addTable('campaign_lead_event_log', $data);

            // Register graphs
            //$event->addGraph($context, 'line', 'mautic.page.graph.line.hits');
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
            case 'campaign_lead_event_log':
                $qb->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
                    ->leftJoin('log', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = log.campaign_id')
                    ->leftJoin('log', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'e.id = log.event_id')
                ;
                $event
                    ->addLeadLeftJoin($qb, 'log')
                    ->addIpAddressLeftJoin($qb, 'log')
                    ->addCategoryLeftJoin($qb, 'c', 'cat')
                    ->addChannelLeftJoins($qb, 'log');
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
        if (!$event->checkContext('campaign_lead_event_log')) {
            return;
        }

        $graphs = $event->getRequestedGraphs();
        $qb     = $event->getQueryBuilder();

        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;

            /** @var ChartQuery $chartQuery */
            $chartQuery = clone $options['chartQuery'];
            $chartQuery->applyDateFilters($queryBuilder, 'date_triggered', 'log');

            switch ($g) {
                /*
                case 'mautic.page.graph.line.hits':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_hit', 'ph');
                    $hits = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans($g), $hits);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;
                */
            }

            unset($queryBuilder);
        }
    }
}
