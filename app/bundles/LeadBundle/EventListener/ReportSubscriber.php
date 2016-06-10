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
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\CoreBundle\Helper\Chart\LineChart;

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
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_GRAPH_GENERATE => ['onReportGraphGenerate', 0]
        ];
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
        if ($event->checkContext(['leads', 'lead.pointlog', 'lead.attribution'])) {
            $prefix     = 'l.';
            $userPrefix = 'u.';
            $ipPrefix   = 'i.';
            $columns    = [
                $ipPrefix.'ip_address'    => [
                    'label' => 'mautic.core.ipaddress',
                    'type'  => 'text'
                ],
                $prefix.'date_identified' => [
                    'label' => 'mautic.lead.report.date_identified',
                    'type'  => 'datetime'
                ],
                $prefix.'points'          => [
                    'label' => 'mautic.lead.points',
                    'type'  => 'int'
                ],
                $prefix.'owner_id'        => [
                    'label' => 'mautic.lead.report.owner_id',
                    'type'  => 'int'
                ],
                $userPrefix.'first_name'  => [
                    'label' => 'mautic.lead.report.owner_firstname',
                    'type'  => 'string'
                ],
                $userPrefix.'last_name'   => [
                    'label' => 'mautic.lead.report.owner_lastname',
                    'type'  => 'string'
                ]
            ];

            /** @var \Mautic\LeadBundle\Model\FieldModel $model */
            $model        = $this->factory->getModel('lead.field');
            $leadFields   = $model->getEntities();
            $fieldColumns = [];
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
                $fieldColumns[$prefix.$f->getAlias()] = [
                    'label' => $f->getLabel(),
                    'type'  => $type
                ];
            }
            $columns = array_merge($columns, $fieldColumns);
            $data    = [
                'display_name' => 'mautic.lead.leads',
                'columns'      => $columns,
            ];

            $event->addTable('leads', $data);

            if ($event->checkContext('lead.attribution')) {
                $attributionPrefix  = 'la.';
                $attributionColumns = [
                    $attributionPrefix.'campaign_name' => [
                        'label' => 'mautic.lead.report.attribution.campaign_name',
                        'type'  => 'string'
                    ],
                    $attributionPrefix.'campaign_id'   => [
                        'label' => 'mautic.lead.report.attribution.campaign_id',
                        'type'  => 'int'
                    ],
                    $attributionPrefix.'date_added'    => [
                        'label' => 'mautic.core.date.added',
                        'type'  => 'datetime'
                    ],
                    $attributionPrefix.'channel'       => [
                        'label' => 'mautic.lead.report.attribution.channel',
                        'type'  => 'string'
                    ],
                    $attributionPrefix.'channel_id'    => [
                        'label' => 'mautic.lead.report.attribution.channel_id',
                        'type'  => 'int'
                    ],
                    $attributionPrefix.'stage_name'    => [
                        'label' => 'mautic.lead.report.attribution.stage_name',
                        'type'  => 'string'
                    ],
                    $attributionPrefix.'stage_id'      => [
                        'label' => 'mautic.lead.report.attribution.stage_id',
                        'type'  => 'int'
                    ],
                    $attributionPrefix.'attribution'   => [
                        'label' => 'mautic.lead.report.attribution',
                        'type'  => 'string'
                    ],
                    $attributionPrefix.'comments'      => [
                        'label' => 'mautic.lead.report.attribution.comments',
                        'type'  => 'string'
                    ],
                    $attributionPrefix.'touch'         => [
                        'label' => 'mautic.lead.report.attribution.touch',
                        'type'  => 'string'
                    ],
                ];

                $filters = $columns = array_merge($columns, $attributionColumns, $event->getIpColumn());

                // Append stage filters
                $filters['stage_id'] = [
                    'label'         => 'mautic.lead.report.attribution.filter.stage',
                    'type'          => 'int',
                    'operatorGroup' => 'multiselect',
                    'list'          => [
                        // @todo add stage lists
                        1 => 'Stage One',
                        2 => 'Stage Two'
                    ],
                ];

                $data = [
                    'display_name' => 'mautic.lead.report.attribution.table',
                    'columns'      => $columns,
                    'filters'      => $filters,
                ];

                $event->addTable('lead.attribution', $data);
            } else {
                // Add graphs
                $event->addGraph('leads', 'line', 'mautic.lead.graph.line.leads');

                if ($event->checkContext('lead.pointlog')) {
                    $pointPrefix  = 'lp.';
                    $pointColumns = [
                        $pointPrefix.'type'        => [
                            'label' => 'mautic.lead.report.points.type',
                            'type'  => 'string'
                        ],
                        $pointPrefix.'event_name'  => [
                            'label' => 'mautic.lead.report.points.event_name',
                            'type'  => 'string'
                        ],
                        $pointPrefix.'action_name' => [
                            'label' => 'mautic.lead.report.points.action_name',
                            'type'  => 'string'
                        ],
                        $pointPrefix.'delta'       => [
                            'label' => 'mautic.lead.report.points.delta',
                            'type'  => 'int'
                        ],
                        $pointPrefix.'date_added'  => [
                            'label' => 'mautic.lead.report.points.date_added',
                            'type'  => 'datetime'
                        ]
                    ];
                    $data         = [
                        'display_name' => 'mautic.lead.report.points.table',
                        'columns'      => array_merge($columns, $pointColumns, $event->getIpColumn())
                    ];
                    $event->addTable('lead.pointlog', $data);

                    // Register graphs
                    $context = 'lead.pointlog';
                    $event->addGraph($context, 'line', 'mautic.lead.graph.line.points');
                    $event->addGraph($context, 'table', 'mautic.lead.table.most.points');
                    $event->addGraph($context, 'table', 'mautic.lead.table.top.countries');
                    $event->addGraph($context, 'table', 'mautic.lead.table.top.cities');
                    $event->addGraph($context, 'table', 'mautic.lead.table.top.events');
                    $event->addGraph($context, 'table', 'mautic.lead.table.top.actions');
                }
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
        if ($context == 'leads') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'l');
            $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
            $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_ips_xref', 'lip', 'lip.lead_id = l.id');
            $qb->leftJoin('lip', MAUTIC_TABLE_PREFIX.'ip_addresses', 'i', 'i.id = lip.ip_id');

            $event->setQueryBuilder($qb);
        } elseif ($context == 'lead.pointlog') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX.'lead_points_change_log', 'lp')
                ->leftJoin('lp', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lp.lead_id')
                ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
            $event->addIpAddressLeftJoin($qb, 'lp');

            $event->setQueryBuilder($qb);
        } elseif ($context == 'lead.attribution') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX.'lead_attribute', 'la')
                ->leftJoin('lp', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lp.lead_id')
                ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id')
                ->leftJoin('c', MAUTIC_TABLE_PREFIX.'campaigns', 'la', 'c.id = a.campaign_id');
                // @todo add stages
                //->leftJoin('a', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = a.campaign_id')
            $event->addIpAddressLeftJoin($qb, 'la');

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
        if (!$event->checkContext(['leads', 'lead.pointlog', 'lead.attribution'])) {

            return;
        }

        $graphs       = $event->getRequestedGraphs();
        $qb           = $event->getQueryBuilder();
        $pointLogRepo = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:PointsChangeLog');

        foreach ($graphs as $g) {
            $queryBuilder = clone $qb;
            $options      = $event->getOptions($g);
            $chartQuery   = clone $options['chartQuery'];
            $chartQuery->applyDateFilters($queryBuilder, 'date_added', 'l');

            switch ($g) {
                case 'mautic.lead.graph.line.leads':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_added', 'l');
                    $leads = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans('mautic.lead.all.leads'), $leads);
                    $queryBuilder->andwhere($qb->expr()->isNotNull('l.date_identified'));
                    $identified = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans('mautic.lead.identified'), $identified);
                    $data         = $chart->render();
                    $data['name'] = $g;
                    $event->setGraph($g, $data);
                    break;

                case 'mautic.lead.graph.line.points':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_added', 'lp');
                    $leads = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans('mautic.lead.graph.line.points'), $leads);
                    $data         = $chart->render();
                    $data['name'] = $g;
                    $event->setGraph($g, $data);
                    break;

                case 'mautic.lead.table.most.points':
                    $queryBuilder->select('l.id, l.email as title, sum(lp.delta) as points')
                        ->groupBy('l.id, l.email')
                        ->orderBy('points', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-asterisk';
                    $graphData['link']      = 'mautic_contact_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.top.countries':
                    $queryBuilder->select('l.country as title, count(l.country) as quantity')
                        ->groupBy('l.country')
                        ->orderBy('quantity', 'DESC');
                    $limit  = 10;
                    $offset = 0;

                    $items                  = $pointLogRepo->getMostLeads($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-globe';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.top.cities':
                    $queryBuilder->select('l.city as title, count(l.city) as quantity')
                        ->groupBy('l.city')
                        ->orderBy('quantity', 'DESC');
                    $limit  = 10;
                    $offset = 0;

                    $items                  = $pointLogRepo->getMostLeads($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-university';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.top.events':
                    $queryBuilder->select('lp.event_name as title, count(lp.event_name) as events')
                        ->groupBy('lp.event_name')
                        ->orderBy('events', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-calendar';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.top.actions':
                    $queryBuilder->select('lp.action_name as title, count(lp.action_name) as actions')
                        ->groupBy('lp.action_name')
                        ->orderBy('actions', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-bolt';
                    $event->setGraph($g, $graphData);
                    break;
            }
            unset($queryBuilder);
        }
    }
}
