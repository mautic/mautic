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
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\StageBundle\Model\StageModel;

/**
 * Class ReportSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class ReportSubscriber extends CommonSubscriber
{
    /**
     * @var ListModel
     */
    protected $listModel;

    /**
     * @var ListModel
     */
    protected $fieldModel;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var StageModel
     */
    protected $stageModel;

    /**
     * ReportSubscriber constructor.
     *
     * @param MauticFactory $factory
     * @param ListModel     $listModel
     * @param FieldModel    $fieldModel
     * @param LeadModel     $leadModel
     * @param StageModel    $stageModel
     */
    public function __construct(MauticFactory $factory, ListModel $listModel, FieldModel $fieldModel, LeadModel $leadModel, StageModel $stageModel)
    {
        parent::__construct($factory);

        $this->listModel  = $listModel;
        $this->fieldModel = $fieldModel;
        $this->leadModel  = $leadModel;
        $this->stageModel = $stageModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_GRAPH_GENERATE => ['onReportGraphGenerate', 0],
            ReportEvents::REPORT_ON_DISPLAY        => ['onReportDisplay', 0]
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
        if ($event->checkContext(['leads', 'lead.pointlog', 'contact.attribution.multi', 'contact.attribution.single'])) {
            $columns = [
                'l.id'              => [
                    'label' => 'mautic.lead.report.contact_id',
                    'type'  => 'int',
                    'link'  => 'mautic_contact_action'
                ],
                'i.ip_address'      => [
                    'label' => 'mautic.core.ipaddress',
                    'type'  => 'text'
                ],
                'l.date_identified' => [
                    'label' => 'mautic.lead.report.date_identified',
                    'type'  => 'datetime'
                ],
                'l.points'          => [
                    'label' => 'mautic.lead.points',
                    'type'  => 'int'
                ],
                'l.owner_id'        => [
                    'label' => 'mautic.lead.report.owner_id',
                    'type'  => 'int',
                    'link'  => 'mautic_user_action'
                ],
                'u.first_name'      => [
                    'label' => 'mautic.lead.report.owner_firstname',
                    'type'  => 'string'
                ],
                'u.last_name'       => [
                    'label' => 'mautic.lead.report.owner_lastname',
                    'type'  => 'string'
                ]
            ];

            $leadFields   = $this->fieldModel->getEntities();
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
                    case 'number':
                        $type = 'float';
                        break;
                    default:
                        $type = 'string';
                        break;
                }
                $fieldColumns['l.'.$f->getAlias()] = [
                    'label' => $f->getLabel(),
                    'type'  => $type
                ];
            }

            $filters = $columns = array_merge($columns, $fieldColumns);

            // Append segment filters
            $userSegments = $this->listModel->getUserLists();
            $list         = [];
            foreach ($userSegments as $segment) {
                $list[$segment['id']] = $segment['name'];
            }
            $filters['s.leadlist_id'] = [
                'alias'     => 'segment_id',
                'label'     => 'mautic.core.filter.lists',
                'type'      => 'select',
                'list'      => $list,
                'operators' => [
                    'eq' => 'mautic.core.operator.equals'
                ]
            ];

            $data = [
                'display_name' => 'mautic.lead.leads',
                'columns'      => $columns,
                'filters'      => $filters,
            ];

            $event->addTable('leads', $data, 'contacts');

            if ($event->checkContext('contact.attribution.multi')) {
                $this->injectAttributionReportData($event, $columns, 'multi');
            }

            if ($event->checkContext('contact.attribution.single')) {
                $this->injectAttributionReportData($event, $columns, 'single');
            } else {
                // Add shared graphs
                $event->addGraph('leads', 'line', 'mautic.lead.graph.line.leads');

                if ($event->checkContext('lead.pointlog')) {
                    $this->injectPointsReportData($event, $columns);
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
        $qb      = $event->getQueryBuilder();

        switch ($context) {
            case 'leads':
                $event->applyDateFilters($qb, 'date_added', 'l');
                $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                    ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_ips_xref', 'lip', 'lip.lead_id = l.id');
                    $event->addIpAddressLeftJoin($qb, 'lip');
                }

                if ($event->hasFilter('s.leadlist_id')) {
                    $qb->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 's', 's.lead_id = l.id AND s.manually_removed = 0');
                }
                break;

            case 'lead.pointlog':
                $event->applyDateFilters($qb, 'date_added', 'lp');
                $qb->from(MAUTIC_TABLE_PREFIX.'lead_points_change_log', 'lp')
                    ->leftJoin('lp', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lp.lead_id')
                    ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_ips_xref', 'lip', 'lip.lead_id = l.id');
                    $event->addIpAddressLeftJoin($qb, 'lp');
                }

                break;

            case 'contact.attribution.multi':
                $event->applyDateFilters($qb, 'attribution_date', 'l');
                $qb->from(MAUTIC_TABLE_PREFIX.'lead_attributions', 'la')
                    ->leftJoin('la', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = la.lead_id')
                    ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');

                break;

            case 'contact.attribution.first':
                $event->applyDateFilters($qb, 'date_added', 'la');
                $qb->from(MAUTIC_TABLE_PREFIX.'lead_attributions', 'la')
                    ->leftJoin('la', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = la.lead_id')
                    ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');

                break;

            case 'contact.attribution.last':
                //$event->applyDateFilters($qb, 'date_added', 'lafirst');

                // Create a subquery for first touch
                $firstQb = clone $qb;
                $minQb   = clone $firstQb;

                $available = [
                    'campaign_id',
                    'campaign_name',
                    'date_added',
                    'channel',
                    'channel_id',
                    'action',
                    'stage_id',
                    'stage_name',
                    'comments'
                ];

                $firstSelect = ['first.lead_id'];
                foreach ($available as $column) {
                    $firstSelect[] = "first.$column as first_{$column}";
                }
                $firstQb->select($firstSelect)
                    ->from(MAUTIC_TABLE_PREFIX.'lead_attributions', 'first');
                $minQb->select('min(mini.id) as first_id')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_attributions', 'mini')
                    ->groupBy('mini.lead_id');
                $firstQb->join('first', sprintf('(%s)', $minQb->getSQL()), 'first_touch', 'first_touch.first_id = first.id');

                $lastQb  = clone $qb;
                $maxQb   = clone $lastQb;

                $lastSelect = ['last.lead_id'];
                foreach ($available as $column) {
                    $lastSelect[] = "last.$column as last_{$column}";
                }
                $lastQb->select($lastSelect)
                    ->from(MAUTIC_TABLE_PREFIX.'lead_attributions', 'last');
                $maxQb->select('max(maxi.id) as last_id')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_attributions', 'maxi')
                    ->groupBy('maxi.lead_id');
                $lastQb->join('last', sprintf('(%s)', $maxQb->getSQL()), 'last_touch', 'last_touch.last_id = last.id');

                $qb->from(sprintf('(%s)', $firstQb->getSQL()), 'lafirst')
                    ->join('lafirst', sprintf('(%s)', $lastQb->getSQL()), 'lalast', 'lafirst.lead_id = lalast.lead_id')
                    ->leftJoin('lafirst', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lafirst.lead_id')
                    ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');

                break;
        }

        $event->setQueryBuilder($qb);
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
        if (!$event->checkContext(['leads', 'lead.pointlog', 'contact.attribution.multi'])) {

            return;
        }

        $graphs       = $event->getRequestedGraphs();
        $qb           = $event->getQueryBuilder();
        $pointLogRepo = $this->leadModel->getPointLogRepository();

        foreach ($graphs as $g) {
            $queryBuilder = clone $qb;
            $options      = $event->getOptions($g);
            /** @var ChartQuery $chartQuery */
            $chartQuery    = clone $options['chartQuery'];
            $attributionQb = clone $queryBuilder;

            $chartQuery->applyDateFilters($queryBuilder, 'date_added', 'l');

            switch ($g) {
                case 'mautic.lead.graph.pie.attribution_stages':
                case 'mautic.lead.graph.pie.attribution_campaigns':
                case 'mautic.lead.graph.pie.attribution_actions':
                case 'mautic.lead.graph.pie.attribution_channels':
                    $attributionQb->resetQueryParts(['select', 'orderBy']);
                    $outerQb = clone $attributionQb;
                    $outerQb->resetQueryParts()
                        ->select('slice, sum(avg_attribution) as total_attribution')
                        ->groupBy('slice');

                    $groupBy = str_replace('mautic.lead.graph.pie.attribution_', '', $g);
                    switch ($groupBy) {
                        case 'stages':
                            $attributionQb->select('CONCAT_WS(\':\', la.stage_id, la.stage_name) as slice, AVG(la.attribution) as avg_attribution')
                                ->groupBy('la.lead_id, la.stage_id');
                            break;
                        case 'campaigns':
                            $attributionQb->select(
                                'CONCAT_WS(\':\', la.campaign_id, la.campaign_name) as slice, AVG(la.attribution) as avg_attribution'
                            )
                                ->groupBy('la.lead_id, la.campaign_id');
                            break;
                        case 'actions':
                            $attributionQb->select('CONCAT_WS(\':\', la.channel, la.action) as slice, AVG(la.attribution) as avg_attribution')
                                ->groupBy('la.lead_id, la.action');
                            break;
                        case 'channels':
                            $attributionQb->select('la.channel as slice, AVG(la.attribution) as avg_attribution')
                                ->groupBy('la.lead_id, la.channel');
                            break;
                    }

                    $outerQb->from(sprintf('(%s) subq', $attributionQb->getSQL()));
                    $outerQb->setParameters(
                        $attributionQb->getParameters()
                    );

                    $chart = new PieChart();
                    $data  = $outerQb->execute()->fetchAll();

                    foreach ($data as $row) {
                        $chart->setDataset($row['slice'], $row['total_attribution']);
                    }

                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-dollar'
                        ]
                    );
                    break;

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

    /**
     * @param ReportBuilderEvent $event
     * @param array              $columns
     */
    private function injectPointsReportData(ReportBuilderEvent $event, array $columns)
    {
        $pointColumns = [
            'lp.type'        => [
                'label' => 'mautic.lead.report.points.type',
                'type'  => 'string'
            ],
            'lp.event_name'  => [
                'label' => 'mautic.lead.report.points.event_name',
                'type'  => 'string'
            ],
            'lp.action_name' => [
                'label' => 'mautic.lead.report.points.action_name',
                'type'  => 'string'
            ],
            'lp.delta'       => [
                'label' => 'mautic.lead.report.points.delta',
                'type'  => 'int'
            ],
            'lp.date_added'  => [
                'label' => 'mautic.lead.report.points.date_added',
                'type'  => 'datetime'
            ]
        ];
        $data         = [
            'display_name' => 'mautic.lead.report.points.table',
            'columns'      => array_merge($columns, $pointColumns, $event->getIpColumn())
        ];
        $event->addTable('lead.pointlog', $data, 'leads');

        // Register graphs
        $context = 'lead.pointlog';
        $event->addGraph($context, 'line', 'mautic.lead.graph.line.points')
            ->addGraph($context, 'table', 'mautic.lead.table.most.points')
            ->addGraph($context, 'table', 'mautic.lead.table.top.countries')
            ->addGraph($context, 'table', 'mautic.lead.table.top.cities')
            ->addGraph($context, 'table', 'mautic.lead.table.top.events')
            ->addGraph($context, 'table', 'mautic.lead.table.top.actions');
    }

    /**
     * @param ReportBuilderEvent $event
     * @param array              $columns
     */
    private function injectAttributionReportData(ReportBuilderEvent $event, array $columns, $type)
    {
        $attributionColumns = [

        ];

        // Unset IP address
        unset($columns['i.ip_address']);

        // Setup stages list
        static $stages;
        if (null == $stages) {
            $userStages = $this->stageModel->getUserStages();
            $stages = [];
            foreach ($userStages as $stage) {
                $stages[$stage['id']] = $stage['name'];
            }
        }

        $context = "contact.attribution.$type";
        if ('multi' == $type) {
            $filters = $columns = array_merge($columns, $attributionColumns);

            // Unset activity attribution for $filters since this data is calculated and not accurately filterable
            unset($filters['la.attribution']);

            // Append stage filters
            $filters['l.stage_id'] = [
                'label' => 'mautic.lead.report.attribution.filter.stage',
                'type'  => 'select',
                'list'  => $stages,
            ];

            $event
                ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_stages')
                ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_campaigns')
                ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_actions')
                ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_channels');
        } else {
            // Add each column as first and last single touch
            $singleTouchColumns = [];
            foreach (['first','last'] as $touchType) {
                foreach ($attributionColumns as $column => $data) {
                    if ('la.attribution' == $column) {
                        continue;
                    }

                    $column                      = str_replace('la.', "la{$touchType}.{$touchType}_", $column);
                    $data['label']               = $data['label'].'_'.$touchType;
                    $singleTouchColumns[$column] = $data;
                }
            }

            $filters = $columns = array_merge($columns, $singleTouchColumns);

            // Append stage filters
            $filters['lafirst.first_stage_id'] = [
                'label' => 'mautic.lead.report.attribution.filter.stage_first',
                'type'  => 'select',
                'list'  => $stages,
            ];
            $filters['lalast.last_stage_id'] = [
                'label' => 'mautic.lead.report.attribution.filter.stage_last',
                'type'  => 'select',
                'list'  => $stages,
            ];
        }

        $data = [
            'display_name' => 'mautic.lead.report.attribution.'.$type,
            'columns'      => $columns,
            'filters'      => $filters,
        ];

        $event->addTable($context, $data, 'contacts');
    }

    /**
     * @param ReportDataEvent $event
     */
    public function onReportDisplay(ReportDataEvent $event)
    {
        if ($data = $event->getData()) {
            $total = $event->getTotalResults();

            if (isset($data[0]['activity_attribution'])) {
                // Divide attribution by total number of results
                foreach ($data as $key => &$row) {
                    if (!empty($row['activity_attribution'])) {
                        $row['activity_attribution'] = round($row['activity_attribution'] / $total, 2);
                    }
                }
            }

            $event->setData($data);
        }
    }
}
