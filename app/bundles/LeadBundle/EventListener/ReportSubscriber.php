<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\StageBundle\Model\StageModel;
use Mautic\UserBundle\Model\UserModel;

/**
 * Class ReportSubscriber.
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
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var array
     */
    protected $channels;

    /**
     * @var array
     */
    protected $channelActions;

    /**
     * ReportSubscriber constructor.
     *
     * @param ListModel     $listModel
     * @param FieldModel    $fieldModel
     * @param LeadModel     $leadModel
     * @param StageModel    $stageModel
     * @param CampaignModel $campaignModel
     * @param UserModel     $userModel
     */
    public function __construct(
        ListModel $listModel,
        FieldModel $fieldModel,
        LeadModel $leadModel,
        StageModel $stageModel,
        CampaignModel $campaignModel,
        UserModel $userModel,
        CompanyModel $companyModel
    ) {
        $this->listModel     = $listModel;
        $this->fieldModel    = $fieldModel;
        $this->leadModel     = $leadModel;
        $this->stageModel    = $stageModel;
        $this->campaignModel = $campaignModel;
        $this->userModel     = $userModel;
        $this->companyModel  = $companyModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_GRAPH_GENERATE => ['onReportGraphGenerate', 0],
            ReportEvents::REPORT_ON_DISPLAY        => ['onReportDisplay', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     *
     * @param ReportBuilderEvent $event
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        $leadContexts    = ['leads', 'lead.pointlog', 'contact.attribution.multi', 'contact.attribution.first', 'contact.attribution.last', 'contact.frequencyrules'];
        $companyContexts = ['companies'];

        if ($event->checkContext($leadContexts)) {
            $columns = [
                'l.id' => [
                    'label' => 'mautic.lead.report.contact_id',
                    'type'  => 'int',
                    'link'  => 'mautic_contact_action',
                ],
                'i.ip_address' => [
                    'label' => 'mautic.core.ipaddress',
                    'type'  => 'text',
                ],
                'l.date_identified' => [
                    'label'          => 'mautic.lead.report.date_identified',
                    'type'           => 'datetime',
                    'groupByFormula' => 'DATE(l.date_identified)',
                ],
                'l.points' => [
                    'label' => 'mautic.lead.points',
                    'type'  => 'int',
                ],
                'l.owner_id' => [
                    'label' => 'mautic.lead.report.owner_id',
                    'type'  => 'int',
                    'link'  => 'mautic_user_action',
                ],
                'u.first_name' => [
                    'label' => 'mautic.lead.report.owner_firstname',
                    'type'  => 'string',
                ],
                'u.last_name' => [
                    'label' => 'mautic.lead.report.owner_lastname',
                    'type'  => 'string',
                ],
            ];

            $leadFields = $this->fieldModel->getEntities([
                'filter' => [
                    'force' => [
                        [
                            'column' => 'f.object',
                            'expr'   => 'like',
                            'value'  => 'lead',
                        ],
                    ],
                ],
            ]);

            $filters = $columns = array_merge($columns, $this->getFieldColumns($leadFields, 'l.'));

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
                    'eq' => 'mautic.core.operator.equals',
                ],
            ];

            $filters['l.owner_id'] = [
                'label' => 'mautic.lead.list.filter.owner',
                'type'  => 'select',
                'list'  => $this->userModel->getRepository()->getUserList('', 0),
            ];

            $data = [
                'display_name' => 'mautic.lead.leads',
                'columns'      => $columns,
                'filters'      => $filters,
            ];

            $event->addTable('leads', $data, 'contacts');

            $attributionTypes = [
                'contact.attribution.multi',
                'contact.attribution.first',
                'contact.attribution.last',
            ];

            if ($event->checkContext($attributionTypes)) {
                $context = $event->getContext();
                foreach ($attributionTypes as $attributionType) {
                    if (empty($context) || $event->checkContext($attributionType)) {
                        $type = str_replace('contact.attribution.', '', $attributionType);
                        $this->injectAttributionReportData($event, $columns, $type);
                    }
                }
            }

            if ($event->checkContext(['leads', 'lead.pointlog'])) {
                // Add shared graphs
                $event->addGraph('leads', 'line', 'mautic.lead.graph.line.leads');

                if ($event->checkContext('lead.pointlog')) {
                    $this->injectPointsReportData($event, $columns);
                }
            }

            if ($event->checkContext(['leads', 'contact.frequencyrules'])) {
                $this->injectFrequencyReportData($event, $columns);
            }
        }

        if ($event->checkContext($companyContexts)) {
            $companyColumns = [
                'comp.id' => [
                    'label' => 'mautic.lead.report.company.company_id',
                    'type'  => 'int',
                    'link'  => 'mautic_company_action',
                ],
                'comp.companyname' => [
                    'label' => 'mautic.lead.report.company.company_name',
                    'type'  => 'string',
                    'link'  => 'mautic_company_action',
                ],
                'comp.companycity' => [
                    'label' => 'mautic.lead.report.company.company_city',
                    'type'  => 'string',
                    'link'  => 'mautic_company_action',
                ],
                'comp.companystate' => [
                    'label' => 'mautic.lead.report.company.company_state',
                    'type'  => 'string',
                    'link'  => 'mautic_company_action',
                ],
                'comp.companycountry' => [
                    'label' => 'mautic.lead.report.company.company_country',
                    'type'  => 'string',
                    'link'  => 'mautic_company_action',
                ],
                'comp.companyindustry' => [
                    'label' => 'mautic.lead.report.company.company_industry',
                    'type'  => 'string',
                    'link'  => 'mautic_company_action',
                ],
            ];
            $companyFields = $this->fieldModel->getEntities([
                'filter' => [
                    'force' => [
                            [
                                'column' => 'f.object',
                                'expr'   => 'like',
                                'value'  => 'company',
                            ],
                        ],
                    ],
                ]);

            $companyFilters = $companyColumns = array_merge($companyColumns, $this->getFieldColumns($companyFields, 'comp.'));

            $data = [
                'display_name' => 'mautic.lead.lead.companies',
                'columns'      => $companyColumns,
                'filters'      => $companyFilters,
            ];

            $event->addTable('companies', $data, 'companies');
            $event->addGraph('companies', 'line', 'mautic.lead.graph.line.companies');
            $event->addGraph('companies', 'pie', 'mautic.lead.graph.pie.companies.industry');
            $event->addGraph('companies', 'pie', 'mautic.lead.table.pie.company.country');
            $event->addGraph('companies', 'table', 'mautic.lead.company.table.top.cities');
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
            case 'leads':
                $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
                }

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_ips_xref', 'lip', 'lip.lead_id = l.id');
                    $event->addIpAddressLeftJoin($qb, 'lip');
                }

                if ($event->hasFilter('s.leadlist_id')) {
                    $qb->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 's', 's.lead_id = l.id AND s.manually_removed = 0');
                    $event->applyDateFilters($qb, 'date_added', 's');
                } else {
                    $event->applyDateFilters($qb, 'date_added', 'l');
                }
                break;

            case 'lead.pointlog':
                $event->applyDateFilters($qb, 'date_added', 'lp');
                $qb->from(MAUTIC_TABLE_PREFIX.'lead_points_change_log', 'lp')
                    ->leftJoin('lp', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lp.lead_id');

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
                }

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_ips_xref', 'lip', 'lip.lead_id = l.id');
                    $event->addIpAddressLeftJoin($qb, 'lp');
                }

                break;
            case 'contact.frequencyrules':
                $event->applyDateFilters($qb, 'date_added', 'lf');
                $qb->from(MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'lf')
                    ->leftJoin('lf', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lf.lead_id');

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
                }

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_ips_xref', 'lip', 'lip.lead_id = l.id');
                    $event->addIpAddressLeftJoin($qb, 'lp');
                }

                break;

            case 'contact.attribution.multi':
            case 'contact.attribution.first':
            case 'contact.attribution.last':
                $localDateTriggered = 'CONVERT_TZ(log.date_triggered,\'UTC\',\''.date_default_timezone_get().'\')';
                $event->applyDateFilters($qb, 'attribution_date', 'l', true);
                $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                    ->join('l', MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log', 'l.id = log.lead_id')
                    ->leftJoin('l', MAUTIC_TABLE_PREFIX.'stages', 's', 'l.stage_id = s.id')
                    ->join('log', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'log.event_id = e.id')
                    ->join('log', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'log.campaign_id = c.id')
                    ->andWhere(
                        $qb->expr()->andX(
                            $qb->expr()->eq('e.event_type', $qb->expr()->literal('decision')),
                            $qb->expr()->eq('log.is_scheduled', 0),
                            $qb->expr()->isNotNull('l.attribution'),
                            $qb->expr()->neq('l.attribution', 0),
                            $qb->expr()->lte("DATE($localDateTriggered)", 'DATE(l.attribution_date)')
                        )
                    );

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
                }

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $event->addIpAddressLeftJoin($qb, 'log');
                }

                if ($event->hasColumn(['cat.id', 'cat.title']) || $event->hasColumn(['cat.id', 'cat.title'])) {
                    $event->addCategoryLeftJoin($qb, 'c', 'cat');
                }

                $subQ = clone $qb;
                $subQ->resetQueryParts();

                $alias = str_replace('contact.attribution.', '', $context);

                $expr = $subQ->expr()->andX(
                    $subQ->expr()->eq("{$alias}e.event_type", $subQ->expr()->literal('decision')),
                    $subQ->expr()->eq("{$alias}log.lead_id", 'log.lead_id')
                );

                $subsetFilters = ['log.campaign_id', 'c.name', 'channel', 'channel_action', 'e.name'];
                if ($event->hasFilter($subsetFilters)) {
                    // Must use the same filters for determining the min of a given subset
                    $filters = $event->getReport()->getFilters();
                    foreach ($filters as $filter) {
                        if (in_array($filter['column'], $subsetFilters)) {
                            $filterParam = $event->createParameterName();
                            if (isset($filter['formula'])) {
                                $x = "({$filter['formula']}) as {$alias}_{$filter['column']}";
                            } else {
                                $x = $alias.$filter['column'];
                            }

                            $expr->add(
                                $expr->{$filter['operator']}($x, ":$filterParam")
                            );
                            $qb->setParameter($filterParam, $filter['value']);
                        }
                    }
                }

                $subQ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', "{$alias}log")
                    ->join("{$alias}log", MAUTIC_TABLE_PREFIX.'campaign_events', "{$alias}e", "{$alias}log.event_id = {$alias}e.id")
                    ->join("{$alias}e", MAUTIC_TABLE_PREFIX.'campaigns', "{$alias}c", "{$alias}e.campaign_id = {$alias}c.id")
                    ->where($expr);

                if ('multi' != $alias) {
                    // Get the min/max row and group by lead for first touch or last touch events
                    $func = ('first' == $alias) ? 'min' : 'max';
                    $subQ->select("$func({$alias}log.date_triggered)")
                        ->setMaxResults(1);
                    $qb->andWhere(
                        $qb->expr()->eq('log.date_triggered', sprintf('(%s)', $subQ->getSQL()))
                    )->groupBy('l.id');
                } else {
                    // Get the total count of records for this lead that match the filters to divide the attribution by
                    $subQ->select('count(*)')
                        ->groupBy("{$alias}log.lead_id");
                    $qb->addSelect(sprintf('(%s) activity_count', $subQ->getSQL()));
                }

                break;
            case 'companies':
                $event->applyDateFilters($qb, 'date_added', 'comp');
                $qb->from(MAUTIC_TABLE_PREFIX.'companies', 'comp');

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('comp', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = comp.owner_id');
                }

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
        if (!$event->checkContext(['leads', 'lead.pointlog', 'contact.attribution.multi', 'companies'])) {
            return;
        }

        $graphs       = $event->getRequestedGraphs();
        $qb           = $event->getQueryBuilder();
        $pointLogRepo = $this->leadModel->getPointLogRepository();
        $companyRepo  = $this->companyModel->getRepository();

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
                        ->select('slice, sum(contact_attribution) as total_attribution')
                        ->groupBy('slice');

                    $groupBy = str_replace('mautic.lead.graph.pie.attribution_', '', $g);
                    switch ($groupBy) {
                        case 'stages':
                            $attributionQb->select('CONCAT_WS(\':\', s.id, s.name) as slice, l.attribution as contact_attribution')
                                ->groupBy('l.id, s.id');
                            break;
                        case 'campaigns':
                            $attributionQb->select(
                                'CONCAT_WS(\':\', c.id, c.name) as slice, l.attribution as contact_attribution'
                            )
                                ->groupBy('l.id, c.id');
                            break;
                        case 'actions':
                            $attributionQb->select('SUBSTRING_INDEX(e.type, \'.\', -1) as slice, l.attribution as contact_attribution')
                                ->groupBy('l.id, SUBSTRING_INDEX(e.type, \'.\', -1)');
                            break;
                        case 'channels':
                            $attributionQb->select('SUBSTRING_INDEX(e.type, \'.\', 1) as slice, l.attribution as contact_attribution')
                                ->groupBy('l.id, SUBSTRING_INDEX(e.type, \'.\', 1)');
                            break;
                    }

                    $outerQb->from(sprintf('(%s) subq', $attributionQb->getSQL()));
                    $outerQb->setParameters(
                        $attributionQb->getParameters()
                    );

                    $chart = new PieChart();
                    $data  = $outerQb->execute()->fetchAll();

                    foreach ($data as $row) {
                        switch ($groupBy) {
                            case 'actions':
                                $label = $this->channelActions[$row['slice']];
                                break;
                            case 'channels':
                                $label = $this->channels[$row['slice']];
                                break;

                            default:
                                $label = (empty($row['slice'])) ? $this->translator->trans('mautic.core.none') : $row['slice'];
                        }
                        $chart->setDataset($label, $row['total_attribution']);
                    }

                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-dollar',
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

                case 'mautic.lead.table.pie.company.country':
                    $counts       = $companyRepo->getCompaniesByGroup($queryBuilder, 'companycountry');
                    $chart        = new PieChart();
                    $companyCount = 0;
                    foreach ($counts as $count) {
                        if ($count['companycountry'] != '') {
                            $chart->setDataset($count['companycountry'], $count['companies']);
                        }
                        $companyCount += $count['companies'];
                    }
                    $chart->setDataset($options['translator']->trans('mautic.lead.all.companies'), $companyCount);
                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa fa-globe',
                        ]
                    );
                    break;
                case 'mautic.lead.graph.line.companies':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_added', 'comp');
                    $companies = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans('mautic.lead.all.companies'), $companies);
                    $data         = $chart->render();
                    $data['name'] = $g;
                    $event->setGraph($g, $data);
                    break;
                case 'mautic.lead.graph.pie.companies.industry':
                    $counts       = $companyRepo->getCompaniesByGroup($queryBuilder, 'companyindustry');
                    $chart        = new PieChart();
                    $companyCount = 0;
                    foreach ($counts as $count) {
                        if ($count['companyindustry'] != '') {
                            $chart->setDataset($count['companyindustry'], $count['companies']);
                        }
                        $companyCount += $count['companies'];
                    }
                    $chart->setDataset($options['translator']->trans('mautic.lead.all.companies'), $companyCount);
                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa fa-industry',
                        ]
                    );
                    break;
                case 'mautic.lead.company.table.top.cities':
                    $queryBuilder->select('comp.companycity as title, count(comp.companycity) as quantity')
                        ->groupBy('comp.companycity')
                        ->andWhere(
                            $queryBuilder->expr()->andX(
                                $queryBuilder->expr()->isNotNull('comp.companycity'),
                                $queryBuilder->expr()->neq('comp.companycity', $queryBuilder->expr()->literal(''))
                            )
                        )
                        ->orderBy('quantity', 'DESC');
                    $limit  = 10;
                    $offset = 0;

                    $items                  = $companyRepo->getMostCompanies($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-building';
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
            'lp.type' => [
                'label' => 'mautic.lead.report.points.type',
                'type'  => 'string',
            ],
            'lp.event_name' => [
                'label' => 'mautic.lead.report.points.event_name',
                'type'  => 'string',
            ],
            'lp.action_name' => [
                'label' => 'mautic.lead.report.points.action_name',
                'type'  => 'string',
            ],
            'lp.delta' => [
                'label' => 'mautic.lead.report.points.delta',
                'type'  => 'int',
            ],
            'lp.date_added' => [
                'label'          => 'mautic.lead.report.points.date_added',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(lp.date_added)',
            ],
        ];
        $data = [
            'display_name' => 'mautic.lead.report.points.table',
            'columns'      => array_merge($columns, $pointColumns, $event->getIpColumn()),
        ];
        $event->addTable('lead.pointlog', $data, 'contacts');

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
    private function injectFrequencyReportData(ReportBuilderEvent $event, array $columns)
    {
        $frequencyColumns = [
            'lf.frequency_number' => [
                'label' => 'mautic.lead.report.frequency.frequency_number',
                'type'  => 'int',
            ],
            'lf.frequency_time' => [
                'label' => 'mautic.lead.report.frequency.frequency_time',
                'type'  => 'string',
            ],
            'lf.channel' => [
                'label' => 'mautic.lead.report.frequency.channel',
                'type'  => 'string',
            ],
            'lf.preferred_channel' => [
                'label' => 'mautic.lead.report.frequency.preferred_channel',
                'type'  => 'boolean',
            ],
            'lf.pause_from_date' => [
                'label' => 'mautic.lead.report.frequency.pause_from_date',
                'type'  => 'datetime',
            ],
            'lf.pause_to_date' => [
                'label' => 'mautic.lead.report.frequency.pause_to_date',
                'type'  => 'datetime',
            ],
            'lf.date_added' => [
                'label'          => 'mautic.lead.report.frequency.date_added',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(lf.date_added)',
            ],
        ];
        $data = [
            'display_name' => 'mautic.lead.report.frequency.messages',
            'columns'      => array_merge($columns, $frequencyColumns, $event->getIpColumn()),
        ];
        $event->addTable('contact.frequencyrules', $data, 'contacts');
    }

    /**
     * @param ReportBuilderEvent $event
     * @param array              $columns
     */
    private function injectAttributionReportData(ReportBuilderEvent $event, array $columns, $type)
    {
        $attributionColumns = [
            'log.campaign_id' => [
                'label' => 'mautic.lead.report.attribution.campaign_id',
                'type'  => 'int',
                'link'  => 'mautic_campaign_action',
            ],
            'log.date_triggered' => [
                'label'          => 'mautic.lead.report.attribution.action_date',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(log.date_triggered)',
            ],
            'c.name' => [
                'alias' => 'campaign_name',
                'label' => 'mautic.lead.report.attribution.campaign_name',
                'type'  => 'string',
            ],
            'l.stage_id' => [
                'label' => 'mautic.lead.report.attribution.stage_id',
                'type'  => 'int',
                'link'  => 'mautic_stage_action',
            ],
            's.name' => [
                'alias' => 'stage_name',
                'label' => 'mautic.lead.report.attribution.stage_name',
                'type'  => 'string',
            ],
            'channel' => [
                'alias'   => 'channel',
                'formula' => 'SUBSTRING_INDEX(e.type, \'.\', 1)',
                'label'   => 'mautic.lead.report.attribution.channel',
                'type'    => 'string',
            ],
            'channel_action' => [
                'alias'   => 'channel_action',
                'formula' => 'SUBSTRING_INDEX(e.type, \'.\', -1)',
                'label'   => 'mautic.lead.report.attribution.channel_action',
                'type'    => 'string',
            ],
            'e.name' => [
                'alias' => 'action_name',
                'label' => 'mautic.lead.report.attribution.action_name',
                'type'  => 'string',
            ],
        ];

        $filters = $columns = array_merge($columns, $event->getCategoryColumns('cat.'), $attributionColumns);

        // Setup available channels
        $availableChannels = $this->campaignModel->getEvents();
        $channels          = [];
        $channelActions    = [];
        foreach ($availableChannels['decision'] as $channel => $decision) {
            $parts                  = explode('.', $channel);
            $channelName            = $parts[0];
            $channels[$channelName] = $this->translator->hasId('mautic.channel.'.$channelName) ? $this->translator->trans(
                'mautic.channel.'.$channelName
            ) : ucfirst($channelName);
            unset($parts[0]);
            $actionValue = implode('.', $parts);

            if ($this->translator->hasId('mautic.channel.action.'.$channel)) {
                $actionName = $this->translator->trans('mautic.channel.action.'.$channel);
            } elseif ($this->translator->hasId('mautic.campaign.'.$channel)) {
                $actionName = $this->translator->trans('mautic.campaign.'.$channel);
            } else {
                $actionName = $channelName.': '.$actionValue;
            }
            $channelActions[$actionValue] = $actionName;
        }
        $filters['channel'] = [
            'label' => 'mautic.lead.report.attribution.channel',
            'type'  => 'select',
            'list'  => $channels,
        ];
        $filters['channel_action'] = [
            'label' => 'mautic.lead.report.attribution.channel_action',
            'type'  => 'select',
            'list'  => $channelActions,
        ];
        $this->channelActions = $channelActions;
        $this->channels       = $channels;
        unset($channelActions, $channels);

        // Setup available channels
        $campaigns                  = $this->campaignModel->getRepository()->getSimpleList();
        $filters['log.campaign_id'] = [
            'label' => 'mautic.lead.report.attribution.filter.campaign',
            'type'  => 'select',
            'list'  => $campaigns,
        ];
        unset($campaigns);

        // Setup stages list
        $userStages = $this->stageModel->getUserStages();
        $stages     = [];
        foreach ($userStages as $stage) {
            $stages[$stage['id']] = $stage['name'];
        }
        $filters['l.stage_id'] = [
            'label' => 'mautic.lead.report.attribution.filter.stage',
            'type'  => 'select',
            'list'  => $stages,
        ];
        unset($stages);

        $context = "contact.attribution.$type";
        $event
            ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_stages')
            ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_campaigns')
            ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_actions')
            ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_channels');

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
        $data = $event->getData();

        if ($event->checkContext(['contact.attribution.first', 'contact.attribution.last', 'contact.attribution.multi', 'contact.message.frequency'])) {
            if (isset($data[0]['channel']) || isset($data[0]['channel_action']) || (isset($data[0]['activity_count']) && isset($data[0]['attribution']))) {
                foreach ($data as $key => &$row) {
                    if (isset($row['channel'])) {
                        $row['channel'] = $this->channels[$row['channel']];
                    }

                    if (isset($row['channel_action'])) {
                        $row['channel_action'] = $this->channelActions[$row['channel_action']];
                    }

                    if (isset($row['activity_count']) && isset($row['attribution'])) {
                        $row['attribution'] = round($row['attribution'] / $row['activity_count'], 2);
                    }

                    if (isset($row['attribution'])) {
                        $row['attribution'] = number_format($row['attribution'], 2);
                    }

                    unset($row);
                }
            }
        }

        $event->setData($data);
        unset($data);
    }

    /**
     * @param $fields
     * @param $prefix
     *
     * @return array
     */
    protected function getFieldColumns($fields, $prefix)
    {
        $columns = [];
        foreach ($fields as $f) {
            switch ($f->getType()) {
                case 'boolean':
                    $type = 'bool';
                    break;
                case 'date':
                    $type = 'date';
                    break;
                case 'datetime':
                    $type = 'datetime';
                    break;
                case 'time':
                    $type = 'time';
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
            $columns[$prefix.$f->getAlias()] = [
                'label' => $f->getLabel(),
                'type'  => $type,
            ];
        }

        return $columns;
    }
}
