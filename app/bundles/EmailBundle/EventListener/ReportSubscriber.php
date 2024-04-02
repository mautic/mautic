<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    public const CONTEXT_EMAILS       = 'emails';

    public const CONTEXT_EMAIL_STATS  = 'email.stats';

    public const EMAILS_PREFIX        = 'e';

    public const EMAIL_STATS_PREFIX   = 'es';

    public const EMAIL_VARIANT_PREFIX = 'vp';

    public const DNC_PREFIX           = 'dnc';

    public const CLICK_PREFIX         = 'cut';

    public const TRACKABLE_PREFIX     = 'tr';

    public const REDIRECT_PREFIX      = 'pr';

    public const CLICK_THROUGH_PREFIX = 'ct';

    public const DNC_COLUMNS = [
        'unsubscribed' => [
            'alias'   => 'unsubscribed',
            'label'   => 'mautic.email.report.unsubscribed',
            'type'    => 'string',
            'formula' => 'IFNULL((SELECT ROUND(SUM(IF('.self::DNC_PREFIX.'.id IS NOT NULL AND '.self::DNC_PREFIX.'.channel_id='.self::EMAILS_PREFIX.'.id AND dnc.reason='.DoNotContact::UNSUBSCRIBED.' , 1, 0)), 1) FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc), 0)',
        ],
        'unsubscribed_ratio' => [
            'alias'   => 'unsubscribed_ratio',
            'label'   => 'mautic.email.report.unsubscribed_ratio',
            'type'    => 'string',
            'formula' => 'IFNULL((SELECT ROUND((SUM(IF('.self::DNC_PREFIX.'.id IS NOT NULL AND '.self::DNC_PREFIX.'.channel_id='.self::EMAILS_PREFIX.'.id AND dnc.reason='.DoNotContact::UNSUBSCRIBED.' , 1, 0))/'.self::EMAILS_PREFIX.'.sent_count)*100, 1) FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc), \'0.0\')',
            'suffix'  => '%',
        ],
        'bounced' => [
            'alias'   => 'bounced',
            'label'   => 'mautic.email.report.bounced',
            'type'    => 'string',
            'formula' => 'IFNULL((SELECT ROUND(SUM(IF('.self::DNC_PREFIX.'.id IS NOT NULL AND '.self::DNC_PREFIX.'.channel_id='.self::EMAILS_PREFIX.'.id AND dnc.reason='.DoNotContact::BOUNCED.' , 1, 0)), 1) FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc), 0)',
        ],
        'bounced_ratio' => [
            'alias'   => 'bounced_ratio',
            'label'   => 'mautic.email.report.bounced_ratio',
            'type'    => 'string',
            'formula' => 'IFNULL((SELECT ROUND((SUM(IF('.self::DNC_PREFIX.'.id IS NOT NULL AND '.self::DNC_PREFIX.'.channel_id='.self::EMAILS_PREFIX.'.id AND dnc.reason='.DoNotContact::BOUNCED.' , 1, 0))/'.self::EMAILS_PREFIX.'.sent_count)*100, 1) FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc), \'0.0\')',
            'suffix'  => '%',
        ],
    ];

    public const EMAIL_STATS_COLUMNS = [
        self::EMAIL_STATS_PREFIX.'.email_address' => [
            'label' => 'mautic.email.report.stat.email_address',
            'type'  => 'email',
        ],
        self::EMAIL_STATS_PREFIX.'.date_sent' => [
            'label'          => 'mautic.email.report.stat.date_sent',
            'type'           => 'datetime',
            'groupByFormula' => 'DATE('.self::EMAIL_STATS_PREFIX.'.date_sent)',
        ],
        self::EMAIL_STATS_PREFIX.'.is_read' => [
            'label' => 'mautic.email.report.stat.is_read',
            'type'  => 'bool',
        ],
        self::EMAIL_STATS_PREFIX.'.is_failed' => [
            'label' => 'mautic.email.report.stat.is_failed',
            'type'  => 'bool',
        ],
        self::EMAIL_STATS_PREFIX.'.viewed_in_browser' => [
            'label' => 'mautic.email.report.stat.viewed_in_browser',
            'type'  => 'bool',
        ],
        self::EMAIL_STATS_PREFIX.'.date_read' => [
            'label'          => 'mautic.email.report.stat.date_read',
            'type'           => 'datetime',
            'groupByFormula' => 'DATE('.self::EMAIL_STATS_PREFIX.'.date_read)',
        ],
        self::EMAIL_STATS_PREFIX.'.retry_count' => [
            'label' => 'mautic.email.report.stat.retry_count',
            'type'  => 'int',
        ],
        self::EMAIL_STATS_PREFIX.'.source' => [
            'label' => 'mautic.report.field.source',
            'type'  => 'string',
        ],
        self::EMAIL_STATS_PREFIX.'.source_id' => [
            'label' => 'mautic.report.field.source_id',
            'type'  => 'int',
        ],
    ];

    public const EMAIL_VARIANT_COLUMNS = [
        self::EMAIL_VARIANT_PREFIX.'.id' => [
            'label' => 'mautic.email.report.variant_parent_id',
            'type'  => 'int',
        ],
        self::EMAIL_VARIANT_PREFIX.'.subject' => [
            'label' => 'mautic.email.report.variant_parent_subject',
            'type'  => 'string',
        ],
    ];

    public const CLICK_COLUMNS = [
        'hits' => [
            'alias'   => 'hits',
            'label'   => 'mautic.email.report.hits_count',
            'type'    => 'string',
            'formula' => 'IFNULL('.self::CLICK_PREFIX.'.hits, 0)',
        ],
        'unique_hits' => [
            'alias'   => 'unique_hits',
            'label'   => 'mautic.email.report.unique_hits_count',
            'type'    => 'string',
            'formula' => 'IFNULL('.self::CLICK_PREFIX.'.unique_hits, 0)',
        ],
        'hits_ratio' => [
            'alias'   => 'hits_ratio',
            'label'   => 'mautic.email.report.hits_ratio',
            'type'    => 'string',
            'formula' => 'IFNULL(ROUND('.self::CLICK_PREFIX.'.hits/('.self::EMAILS_PREFIX.'.sent_count)*100, 1), \'0.0\')',
            'suffix'  => '%',
        ],
        'unique_ratio' => [
            'alias'   => 'unique_ratio',
            'label'   => 'mautic.email.report.unique_ratio',
            'type'    => 'string',
            'formula' => 'IFNULL(ROUND('.self::CLICK_PREFIX.'.unique_hits/('.self::EMAILS_PREFIX.'.sent_count)*100, 1), \'0.0\')',
            'suffix'  => '%',
        ],
    ];

    public function __construct(
        private Connection $db,
        private CompanyReportData $companyReportData,
        private StatRepository $statRepository,
        private GeneratedColumnsProviderInterface $generatedColumnsProvider,
        private FieldsBuilder $fieldsBuilder
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_GRAPH_GENERATE => ['onReportGraphGenerate', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     */
    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT_EMAILS, self::CONTEXT_EMAIL_STATS])) {
            return;
        }

        $prefix  = self::EMAILS_PREFIX.'.';
        $columns = [
            $prefix.'subject' => [
                'label' => 'mautic.email.subject',
                'type'  => 'string',
            ],
            $prefix.'lang' => [
                'label' => 'mautic.core.language',
                'type'  => 'string',
            ],
            $prefix.'read_count' => [
                'label' => 'mautic.email.report.read_count',
                'type'  => 'int',
            ],
            'read_ratio' => [
                'alias'   => 'read_ratio',
                'label'   => 'mautic.email.report.read_ratio',
                'type'    => 'string',
                'formula' => 'IFNULL(ROUND(('.$prefix.'read_count/'.$prefix.'sent_count)*100, 1), \'0.0\')',
                'suffix'  => '%',
            ],
            $prefix.'sent_count' => [
                'label' => 'mautic.email.report.sent_count',
                'type'  => 'int',
            ],
            $prefix.'revision' => [
                'label' => 'mautic.email.report.revision',
                'type'  => 'int',
            ],
            $prefix.'variant_start_date' => [
                'label'          => 'mautic.email.report.variant_start_date',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE('.$prefix.'variant_start_date)',
            ],
            $prefix.'variant_sent_count' => [
                'label' => 'mautic.email.report.variant_sent_count',
                'type'  => 'int',
            ],
            $prefix.'variant_read_count' => [
                'label' => 'mautic.email.report.variant_read_count',
                'type'  => 'int',
            ],
            'click_through_count' => [
                'alias'   => 'click_through_count',
                'label'   => 'mautic.email.report.click_through_count',
                'type'    => 'string',
                'formula' => 'IFNULL('.self::CLICK_THROUGH_PREFIX.'.click_through_count, 0)',
            ],
            'click_through_rate' => [
                'alias'   => 'click_through_rate',
                'label'   => 'mautic.email.report.click_through_rate',
                'type'    => 'string',
                'formula' => 'IFNULL(ROUND('.self::CLICK_THROUGH_PREFIX.'.click_through_count/'.$prefix.'sent_count * 100, 1), \'0.0\')',
                'suffix'  => '%',
            ],
            'click_to_open_rate' => [
                'alias'   => 'click_to_open_rate',
                'label'   => 'mautic.email.report.click_to_open_rate',
                'type'    => 'string',
                'formula' => 'IFNULL(ROUND('.self::CLICK_THROUGH_PREFIX.'.click_through_count/'.$prefix.'read_count * 100, 1), \'0.0\')',
                'suffix'  => '%',
            ],
        ];

        $columns = array_merge(
            $columns,
            $event->getStandardColumns($prefix, [], 'mautic_email_action'),
            $event->getCategoryColumns(),
            self::DNC_COLUMNS,
            self::EMAIL_VARIANT_COLUMNS,
            self::CLICK_COLUMNS
        );
        $data = [
            'display_name' => 'mautic.email.emails',
            'columns'      => $columns,
        ];
        $event->addTable(self::CONTEXT_EMAILS, $data);
        $context = self::CONTEXT_EMAILS;
        $event->addGraph($context, 'pie', 'mautic.email.graph.pie.read.ingored.unsubscribed.bounced');
        $event->addGraph($context, 'table', 'mautic.email.table.most.emails.clicks');

        if ($event->checkContext(self::CONTEXT_EMAIL_STATS)) {
            // Ratios are not applicable for individual stats
            unset($columns['read_ratio'], $columns['unsubscribed_ratio'], $columns['bounced_ratio'], $columns['hits_ratio'], $columns['unique_ratio']);

            // Click through value are not applicable for individual stats
            unset($columns['click_through_count'], $columns['click_through_rate'], $columns['click_to_open_rate']);

            // Email counts are not applicable for individual stats
            unset($columns[$prefix.'read_count'], $columns[$prefix.'variant_sent_count'], $columns[$prefix.'variant_read_count']);

            // Prevent null DNC records from filtering the results
            $columns['unsubscribed']['type']    = 'bool';
            $columns['unsubscribed']['formula'] = 'IF(dnc.id IS NOT NULL AND dnc.reason='.DoNotContact::UNSUBSCRIBED.', 1, 0)';

            $columns['bounced']['type']    = 'bool';
            $columns['bounced']['formula'] = 'IF(dnc.id IS NOT NULL AND dnc.reason='.DoNotContact::BOUNCED.', 1, 0)';

            // clicked column for individual stats
            $columns['is_hit'] = [
                'alias'   => 'is_hit',
                'label'   => 'mautic.email.report.is_hit',
                'type'    => 'bool',
                'formula' => 'IF('.self::CLICK_PREFIX.'.hits is NULL, 0, 1)',
            ];

            // time between sent and read
            $columns['read_delay'] = [
                'alias'   => 'read_delay',
                'label'   => 'mautic.email.report.read.delay',
                'type'    => 'string',
                'formula' => 'IF(es.date_read IS NOT NULL, TIMEDIFF(es.date_read, es.date_sent), \'-\')',
            ];

            $columns = array_merge(
                $columns,
                self::EMAIL_STATS_COLUMNS,
                $event->getCampaignByChannelColumns(),
                $event->getLeadColumns(),
                $event->getIpColumn(),
                $this->companyReportData->getCompanyData()
            );

            $filters = array_merge(
                $columns,
                $this->fieldsBuilder->getLeadFilter('l.', 's.')
            );

            $data = [
                'display_name' => 'mautic.email.stats.report.table',
                'columns'      => $columns,
                'filters'      => $filters,
            ];
            $event->addTable(self::CONTEXT_EMAIL_STATS, $data, self::CONTEXT_EMAILS);

            // Register Graphs
            $context = self::CONTEXT_EMAIL_STATS;
            $event->addGraph($context, 'line', 'mautic.email.graph.line.stats');
            $event->addGraph($context, 'pie', 'mautic.email.graph.pie.ignored.read.failed');
            $event->addGraph($context, 'table', 'mautic.email.table.most.emails.sent');
            $event->addGraph($context, 'table', 'mautic.email.table.most.emails.read');
            $event->addGraph($context, 'table', 'mautic.email.table.most.emails.read.percent');
            $event->addGraph($context, 'table', 'mautic.email.table.most.emails.unsubscribed');
            $event->addGraph($context, 'table', 'mautic.email.table.most.emails.bounced');
            $event->addGraph($context, 'table', 'mautic.email.table.most.emails.failed');
            $event->addGraph($context, 'table', 'mautic.email.table.most.emails.clicks');
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        $context    = $event->getContext();
        $qb         = $event->getQueryBuilder();
        $hasGroupBy = $event->hasGroupBy();

        $qbcut                  = $this->db->createQueryBuilder(); // channel_url_trackables subquery
        $qbct                   = $this->db->createQueryBuilder(); // click-though subquery
        $useDncColumns          = $event->usesColumn(array_keys(self::DNC_COLUMNS));
        $useVariantColumns      = $event->usesColumn(array_keys(self::EMAIL_VARIANT_COLUMNS));
        $useClickColumns        = $event->usesColumn(array_keys(self::CLICK_COLUMNS)) || $event->usesColumn('is_hit');
        $useClickThroughColumns = $event->usesColumn(['click_through_count', 'click_through_rate', 'click_to_open_rate']);

        switch ($context) {
            case self::CONTEXT_EMAILS:
                $qb->from(MAUTIC_TABLE_PREFIX.'emails', self::EMAILS_PREFIX)
                    ->leftJoin(self::EMAILS_PREFIX, MAUTIC_TABLE_PREFIX.'emails', self::EMAIL_VARIANT_PREFIX, 'vp.id = e.variant_parent_id');

                $event->addCategoryLeftJoin($qb, self::EMAILS_PREFIX)
                    ->applyDateFilters($qb, 'date_added', self::EMAILS_PREFIX);

                if (!$hasGroupBy) {
                    $qb->groupBy('e.id');
                }

                if ($useClickColumns) {
                    $qbcut->select(
                        'COUNT(cut2.channel_id) AS trackable_count, SUM(cut2.hits) AS hits',
                        'SUM(cut2.unique_hits) AS unique_hits',
                        'cut2.channel_id'
                    )
                        ->from(MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut2')
                        ->where('cut2.channel = \'email\'')
                        ->groupBy('cut2.channel_id');
                    $qb->leftJoin(self::EMAILS_PREFIX, sprintf('(%s)', $qbcut->getSQL()), self::CLICK_PREFIX, 'e.id = cut.channel_id');
                }

                if ($useDncColumns) {
                    $this->addDNCTableForEmails($qb);
                }

                if ($useClickThroughColumns) {
                    $qbct->select(
                        'COUNT(DISTINCT ph.lead_id) AS click_through_count',
                        'cut.channel_id',
                    )
                        ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
                        ->innerJoin('ph', MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut', 'cut.redirect_id = ph.redirect_id AND cut.channel_id = ph.source_id')
                        ->groupBy('cut.channel_id');
                    $qb->leftJoin(self::EMAILS_PREFIX, sprintf('(%s)', $qbct->getSQL()), self::CLICK_THROUGH_PREFIX, 'e.id = ct.channel_id');
                }

                break;
            case self::CONTEXT_EMAIL_STATS:
                $qb->from(MAUTIC_TABLE_PREFIX.'email_stats', self::EMAIL_STATS_PREFIX);

                if ($event->usesColumnWithPrefix(self::EMAILS_PREFIX)
                    || $event->usesColumnWithPrefix(ReportGeneratorEvent::CATEGORY_PREFIX)
                    || $useVariantColumns
                ) {
                    $qb->leftJoin(self::EMAIL_STATS_PREFIX, MAUTIC_TABLE_PREFIX.'emails', self::EMAILS_PREFIX, 'e.id = es.email_id');
                }

                if ($useVariantColumns) {
                    $qb->leftJoin(self::EMAILS_PREFIX, MAUTIC_TABLE_PREFIX.'emails', self::EMAIL_VARIANT_PREFIX, 'vp.id = e.variant_parent_id');
                }

                if ($useDncColumns) {
                    $this->addDNCTableForEmailStats($qb);
                }

                if ($event->hasFilter('s.leadlist_id')) {
                    $qb->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 's', 's.lead_id = l.id AND s.manually_removed = 0');
                }

                $event->addCategoryLeftJoin($qb, self::EMAILS_PREFIX)
                    ->addLeadLeftJoin($qb, self::EMAIL_STATS_PREFIX)
                    ->addIpAddressLeftJoin($qb, self::EMAIL_STATS_PREFIX)
                    ->applyDateFilters($qb, 'date_sent', self::EMAIL_STATS_PREFIX);
                if ($useClickColumns) {
                    $qbcut->select(
                        'COUNT(ph.id) AS hits',
                        'COUNT(DISTINCT(ph.redirect_id)) AS unique_hits',
                        'cut2.channel_id',
                        'ph.lead_id'
                    )
                        ->from(MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut2')
                        ->join(
                            'cut2',
                            MAUTIC_TABLE_PREFIX.'page_hits',
                            'ph',
                            'cut2.redirect_id = ph.redirect_id AND cut2.channel_id = ph.source_id'
                        )
                        ->where('cut2.channel = \'email\' AND ph.source = \'email\'')
                        ->groupBy('cut2.channel_id, ph.lead_id');

                    if ($event->hasFilter('e.id')) {
                        $filterParam = $event->createParameterName();
                        $qbcut->andWhere($qb->expr()->in('cut2.channel_id', ":{$filterParam}"));
                        $qb->setParameter($filterParam, $event->getFilterValues('e.id'), ArrayParameterType::INTEGER);
                    }

                    $qb->leftJoin(
                        self::EMAIL_STATS_PREFIX,
                        "({$qbcut->getSQL()})",
                        self::CLICK_PREFIX,
                        'es.email_id = cut.channel_id AND es.lead_id = cut.lead_id'
                    );
                }

                $event->addCampaignByChannelJoin(
                    $qb,
                    self::EMAIL_STATS_PREFIX,
                    'email',
                    ReportGeneratorEvent::CONTACT_PREFIX,
                    'email_id'
                );

                if ($this->companyReportData->eventHasCompanyColumns($event)) {
                    $event->addCompanyLeftJoin($qb);
                }

                if (!$event->hasGroupBy()) {
                    $qb->groupBy('es.id');
                }

                break;
        }

        $event->setQueryBuilder($qb);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGraphGenerate(ReportGraphEvent $event): void
    {
        $graphs = $event->getRequestedGraphs();

        if (!$event->checkContext([self::CONTEXT_EMAIL_STATS, self::CONTEXT_EMAILS])) {
            return;
        }

        if ($event->checkContext(self::CONTEXT_EMAILS)
            && !in_array('mautic.email.graph.pie.read.ingored.unsubscribed.bounced', $graphs)
            && !in_array('mautic.email.table.most.emails.clicks', $graphs)) {
            return;
        }

        $qb = $event->getQueryBuilder();
        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;
            /** @var ChartQuery $chartQuery */
            $chartQuery   = clone $options['chartQuery'];
            $origQuery    = clone $queryBuilder;
            // just limit date for contacts emails
            if ($event->checkContext(self::CONTEXT_EMAIL_STATS)) {
                $chartQuery->applyDateFilters($queryBuilder, 'date_sent', self::EMAIL_STATS_PREFIX);
            }

            switch ($g) {
                case 'mautic.email.graph.line.stats':
                    $chartQuery->setGeneratedColumnProvider($this->generatedColumnsProvider);
                    $chart     = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $sendQuery = clone $queryBuilder;
                    $readQuery = clone $origQuery;
                    $readQuery->andWhere($qb->expr()->isNotNull('date_read'));
                    $failedQuery = clone $queryBuilder;
                    $failedQuery->andWhere($qb->expr()->eq('es.is_failed', ':true'));
                    $failedQuery->setParameter('true', true, 'boolean');
                    $chartQuery->applyDateFilters($readQuery, 'date_read', self::EMAIL_STATS_PREFIX);
                    $chartQuery->modifyTimeDataQuery($sendQuery, 'date_sent', self::EMAIL_STATS_PREFIX);
                    $chartQuery->modifyTimeDataQuery($readQuery, 'date_read', self::EMAIL_STATS_PREFIX);
                    $chartQuery->modifyTimeDataQuery($failedQuery, 'date_sent', self::EMAIL_STATS_PREFIX);
                    $sends  = $chartQuery->loadAndBuildTimeData($sendQuery);
                    $reads  = $chartQuery->loadAndBuildTimeData($readQuery);
                    $failes = $chartQuery->loadAndBuildTimeData($failedQuery);
                    $chart->setDataset($options['translator']->trans('mautic.email.sent.emails'), $sends);
                    $chart->setDataset($options['translator']->trans('mautic.email.read.emails'), $reads);
                    $chart->setDataset($options['translator']->trans('mautic.email.failed.emails'), $failes);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;

                case 'mautic.email.graph.pie.ignored.read.failed':
                    $counts = $this->statRepository->getIgnoredReadFailed($queryBuilder);
                    $chart  = new PieChart();
                    $chart->setDataset($options['translator']->trans('mautic.email.read.emails'), $counts['read']);
                    $chart->setDataset($options['translator']->trans('mautic.email.failed.emails'), $counts['failed']);
                    $chart->setDataset(
                        $options['translator']->trans('mautic.email.ignored.emails'),
                        $counts['ignored']
                    );
                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-flag-checkered',
                        ]
                    );
                    break;

                case 'mautic.email.graph.pie.read.ingored.unsubscribed.bounced':
                    $queryBuilder->select('SUM(DISTINCT e.sent_count) as sent_count,
                        SUM(DISTINCT e.read_count) as read_count,
                        count(CASE WHEN '.self::DNC_PREFIX.'.id and '.self::DNC_PREFIX.'.reason = '.DoNotContact::UNSUBSCRIBED.' THEN 1 ELSE null END) as unsubscribed,
                        count(CASE WHEN '.self::DNC_PREFIX.'.id and '.self::DNC_PREFIX.'.reason = '.DoNotContact::BOUNCED.' THEN 1 ELSE null END) as bounced'
                    );
                    $this->addDNCTableForEmails($queryBuilder);
                    $queryBuilder->resetQueryPart('groupBy');
                    $counts = $queryBuilder->execute()->fetchAssociative();
                    $chart  = new PieChart();
                    $chart->setDataset(
                        $options['translator']->trans('mautic.email.stat.read'),
                        $counts['read_count'] ?? 0
                    );
                    $chart->setDataset(
                        $options['translator']->trans('mautic.email.graph.pie.ignored.read.failed.ignored'),
                        ($counts['sent_count'] ?? 0) - ($counts['read_count'] ?? 0)
                    );
                    $chart->setDataset(
                        $options['translator']->trans('mautic.email.unsubscribed'),
                        $counts['unsubscribed'] ?? 0
                    );
                    $chart->setDataset(
                        $options['translator']->trans('mautic.email.bounced'),
                        $counts['bounced'] ?? 0
                    );

                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-flag-checkered',
                        ]
                    );
                    break;

                case 'mautic.email.table.most.emails.sent':
                    $this->joinEmailsTableIfMissing($queryBuilder, $event);
                    $queryBuilder->select('e.id, e.subject as title, SUM(DISTINCT e. sent_count) as sent')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('sent', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $this->statRepository->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-paper-plane-o';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.read':
                    $this->joinEmailsTableIfMissing($queryBuilder, $event);
                    $queryBuilder->select('e.id, e.subject as title, SUM(DISTINCT e. read_count) as opens')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('opens', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $this->statRepository->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-eye';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.failed':
                    $this->joinEmailsTableIfMissing($queryBuilder, $event);
                    $queryBuilder->select(
                        'e.id, e.subject as title, count(CASE WHEN es.is_failed THEN 1 ELSE null END) as failed'
                    )
                        ->having('count(CASE WHEN es.is_failed THEN 1 ELSE null END) > 0')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('failed', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $this->statRepository->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-exclamation-triangle';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.unsubscribed':
                    $this->joinEmailsTableIfMissing($queryBuilder, $event);
                    $this->addDNCTableForEmailStats($queryBuilder);
                    $queryBuilder->select(
                        'e.id, e.subject as title, count(CASE WHEN dnc.id  and dnc.reason = '.DoNotContact::UNSUBSCRIBED.' THEN 1 ELSE null END) as unsubscribed'
                    )
                        ->having(
                            'count(CASE WHEN dnc.id and dnc.reason = '.DoNotContact::UNSUBSCRIBED.' THEN 1 ELSE null END) > 0'
                        )
                        ->groupBy('e.id, e.subject')
                        ->orderBy('unsubscribed', 'DESC');

                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $this->statRepository->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-exclamation-triangle';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.bounced':
                    $this->joinEmailsTableIfMissing($queryBuilder, $event);
                    $this->addDNCTableForEmailStats($queryBuilder);
                    $queryBuilder->select(
                        'e.id, e.subject as title, count(CASE WHEN dnc.id  and dnc.reason = '.DoNotContact::BOUNCED.' THEN 1 ELSE null END) as bounced'
                    )
                        ->having(
                            'count(CASE WHEN dnc.id and dnc.reason = '.DoNotContact::BOUNCED.' THEN 1 ELSE null END) > 0'
                        )
                        ->groupBy('e.id, e.subject')
                        ->orderBy('bounced', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $this->statRepository->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-exclamation-triangle';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.read.percent':
                    $this->joinEmailsTableIfMissing($queryBuilder, $event);
                    $queryBuilder->select('e.id, e.subject as title, round(e.read_count / e.sent_count * 100) as ratio')
                        ->groupBy('e.id, e.subject')
                        ->orderBy('ratio', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $this->statRepository->getMostEmails($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-tachometer';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.email.table.most.emails.clicks':
                    $this->addTrackableTablesForEmailStats($queryBuilder);
                    $queryBuilder->select('e.id, e.subject as `title`, tr.hits as `clicks`, tr.unique_hits as `unique clicks`, pr.url as `URL`')
                        ->andWhere('pr.url IS NOT NULL')
                        ->groupBy('e.id, tr.redirect_id, tr.hits')
                        ->orderBy('tr.hits', 'DESC')
                        ->setMaxResults(10);

                    $items                  = $queryBuilder->execute()->fetchAllAssociative();
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-external-link-square';
                    $graphData['link']      = 'mautic_email_action';
                    $event->setGraph($g, $graphData);
                    break;
            }
            unset($queryBuilder);
        }
    }

    private function joinEmailsTableIfMissing(QueryBuilder $queryBuilder, ReportGraphEvent $event): void
    {
        if ($event->checkContext(self::CONTEXT_EMAIL_STATS) && !$this->isJoined($queryBuilder, MAUTIC_TABLE_PREFIX.'emails', self::EMAIL_STATS_PREFIX, self::EMAILS_PREFIX)) {
            $queryBuilder->leftJoin(self::EMAIL_STATS_PREFIX, MAUTIC_TABLE_PREFIX.'emails', self::EMAILS_PREFIX, 'e.id = es.email_id');
        }
    }

    /**
     * Add the Do Not Contact table to the query builder.
     */
    private function addDNCTableForEmails(QueryBuilder $qb): void
    {
        $table = MAUTIC_TABLE_PREFIX.'lead_donotcontact';

        if (!$this->isJoined($qb, $table, self::EMAILS_PREFIX, self::DNC_PREFIX)) {
            $qb->leftJoin(
                self::EMAILS_PREFIX,
                $table,
                self::DNC_PREFIX,
                'e.id = dnc.channel_id AND dnc.channel=\'email\''
            );
        }
    }

    private function addTrackableTablesForEmailStats(QueryBuilder $qb): void
    {
        $trTable = MAUTIC_TABLE_PREFIX.'channel_url_trackables';
        $prTable = MAUTIC_TABLE_PREFIX.'page_redirects';

        if (!$this->isJoined($qb, $trTable, self::EMAILS_PREFIX, self::TRACKABLE_PREFIX)) {
            $qb->leftJoin(
                self::EMAILS_PREFIX,
                $trTable,
                self::TRACKABLE_PREFIX,
                'e.id = tr.channel_id AND tr.channel = \'email\''
            );
        }
        if (!$this->isJoined($qb, $prTable, self::TRACKABLE_PREFIX, self::REDIRECT_PREFIX)) {
            $qb->leftJoin(
                self::TRACKABLE_PREFIX,
                $prTable,
                self::REDIRECT_PREFIX,
                'tr.redirect_id = pr.id'
            );
        }
    }

    /**
     * Add the Do Not Contact table to the query builder.
     */
    private function addDNCTableForEmailStats(QueryBuilder $qb): void
    {
        $table = MAUTIC_TABLE_PREFIX.'lead_donotcontact';

        if (!$this->isJoined($qb, $table, self::EMAIL_STATS_PREFIX, self::DNC_PREFIX)) {
            $qb->leftJoin(
                self::EMAIL_STATS_PREFIX,
                $table,
                self::DNC_PREFIX,
                'es.email_id = dnc.channel_id AND dnc.channel=\'email\' AND es.lead_id = dnc.lead_id'
            );
        }
    }

    private function isJoined($query, $table, $fromAlias, $alias): bool
    {
        $joins = $query->getQueryParts()['join'];
        if (empty($joins) || (!empty($joins) && empty($joins[$fromAlias]))) {
            return false;
        }

        foreach ($joins[$fromAlias] as $join) {
            if ($join['joinTable'] == $table && $join['joinAlias'] == $alias) {
                return true;
            }
        }

        return false;
    }
}
