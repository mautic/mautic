<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DynamicContentReportSubscriber implements EventSubscriberInterface
{
    public const CONTEXT_DWC          = 'dwc';
    public const DWC_PREFIX           = 'dwc';
    public const DWC_STAT_PREFIX      = 'dwc_stat';
    public const EMAIL_PREFIX         = 'e';
    public const PAGE_PREFIX          = 'p';
    public const SEGMENT_PREFIX       = 'ls';
    public const SEGMENT_XREF_PREFIX  = 'elx';

    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @return array<mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     */
    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT_DWC])) {
            return;
        }

        // Dynamic Content columns
        $dwcPrefix  = self::DWC_PREFIX.'.';
        $dwcColumns = [
            $dwcPrefix.'id' => [
                'label' => 'mautic.core.id',
                'type'  => 'int',
                'alias' => 'dwc_id',
            ],
            $dwcPrefix.'name' => [
                'label' => 'mautic.core.dynamicContent.token_name',
                'type'  => 'string',
                'alias' => 'dwc_name',
            ],
            $dwcPrefix.'slot_name' => [
                'label' => 'mautic.dynamicContent.label.slot_name',
                'type'  => 'string',
                'alias' => 'dwc_slot_name',
            ],
            $dwcPrefix.'description' => [
                'label' => 'mautic.core.description',
                'type'  => 'string',
                'alias' => 'dwc_description',
            ],
            $dwcPrefix.'is_campaign_based' => [
                'label' => 'mautic.dynamicContent.slot.campaign',
                'type'  => 'bool',
                'alias' => 'dwc_is_campaign_based',
            ],
            $dwcPrefix.'priority' => [
                'alias'   => 'priority',
                'label'   => 'mautic.dynamicContent.label.order',
                'type'    => 'int',
                'formula' => 'IFNULL('.$dwcPrefix.'display_order, 0)',
            ],
        ];

        // DWC Stats columns
        $statPrefix  = self::DWC_STAT_PREFIX.'.';
        $statColumns = [
            $statPrefix.'id' => [
                'label' => 'mautic.dynamicContent.report.stat.id',
                'type'  => 'int',
                'alias' => 'dwc_stat_id',
            ],
            $statPrefix.'date_sent' => [
                'label'          => 'mautic.email.report.stat.date_sent',
                'type'           => 'datetime',
                'alias'          => 'dwc_date_sent',
            ],
            $statPrefix.'source' => [
                'label' => 'mautic.core.source',
                'type'  => 'string',
                'alias' => 'dwc_stat_source',
            ],
            $statPrefix.'source_id' => [
                'label' => 'mautic.dynamicContent.report.stat.source_id',
                'type'  => 'int',
                'alias' => 'dwc_stat_source_id',
            ],
            $statPrefix.'token_placement' => [
                'alias'   => 'target',
                'label'   => 'mautic.dynamicContent.report.target',
                'type'    => 'string',
                'formula' => sprintf(
                    'CASE WHEN %stoken_placement = \'subject\' THEN \'%s\' ELSE \'%s\' END',
                    $statPrefix,
                    $this->translator->trans('mautic.dynamicContent.report.subject_line'),
                    $this->translator->trans('mautic.dynamicContent.report.body'),
                ),
            ],
        ];

        // Email columns
        $emailPrefix  = self::EMAIL_PREFIX.'.';
        $emailColumns = [
            $emailPrefix.'id' => [
                'label' => 'mautic.dashboard.label.email.id',
                'type'  => 'int',
                'alias' => 'email_id',
                'link'  => 'mautic_email_action',
            ],
            $emailPrefix.'name' => [
                'label' => 'mautic.dashboard.label.email.name',
                'type'  => 'string',
                'alias' => 'email_name',
            ],
            $emailPrefix.'subject' => [
                'label' => 'mautic.page.report.hits.email_subject',
                'type'  => 'string',
                'alias' => 'email_subject',
            ],
            $emailPrefix.'date_added' => [
                'label'          => 'mautic.dynamicContent.report.email.date_added',
                'type'           => 'datetime',
                'alias'          => 'email_date_added',
            ],
        ];

        // Segment columns
        $segmentPrefix  = self::SEGMENT_PREFIX.'.';
        $segmentColumns = [
            $segmentPrefix.'id' => [
                'label'   => 'mautic.dynamicContent.report.lead_list.id',
                'type'    => 'string',
                'alias'   => 'segment_id',
                'formula' => 'group_concat('.$segmentPrefix."id SEPARATOR ', ')",
            ],
            $segmentPrefix.'name' => [
                'label'   => 'mautic.dynamicContent.report.lead_list.name',
                'type'    => 'string',
                'alias'   => 'segment_name',
                'formula' => 'group_concat('.$segmentPrefix."name SEPARATOR ', ')",
            ],
        ];

        // Page columns
        $pagePrefix  = self::PAGE_PREFIX.'.';
        $pageColumns = [
            $pagePrefix.'id' => [
                'label'   => 'mautic.form.report.page_id',
                'type'    => 'int',
                'alias'   => 'page_id',
                'link'    => 'mautic_page_action',
            ],
            $pagePrefix.'title' => [
                'label' => 'mautic.form.report.page_name',
                'alias' => 'page_name',
                'type'  => 'string',
            ],
        ];

        $columns = array_merge(
            $dwcColumns,
            $statColumns,
            $emailColumns,
            $segmentColumns,
            $pageColumns,
            $event->getLeadColumns(),
            $event->getCategoryColumns()
        );

        $event->addTable(
            self::CONTEXT_DWC,
            [
                'display_name' => 'mautic.dynamicContent.dynamicContent',
                'columns'      => $columns,
            ]
        );
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT_DWC])) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'dynamic_content', self::DWC_PREFIX)
            ->leftJoin(
                self::DWC_PREFIX,
                MAUTIC_TABLE_PREFIX.'dynamic_content_stats',
                self::DWC_STAT_PREFIX,
                sprintf('%s.id = %s.dynamic_content_id',
                    self::DWC_PREFIX,
                    self::DWC_STAT_PREFIX
                )
            );
        if ($event->usesColumnWithPrefix(self::EMAIL_PREFIX) || $event->usesColumnWithPrefix(self::SEGMENT_PREFIX)) {
            $queryBuilder->leftJoin(
                self::DWC_STAT_PREFIX,
                MAUTIC_TABLE_PREFIX.'emails',
                self::EMAIL_PREFIX,
                sprintf(
                    '%1$s.source_id = %2$s.id AND %1$s.source = "email"',
                    self::DWC_STAT_PREFIX,
                    self::EMAIL_PREFIX
                )
            );
        }

        if ($event->usesColumnWithPrefix(self::PAGE_PREFIX)) {
            $queryBuilder->leftJoin(
                self::DWC_STAT_PREFIX,
                MAUTIC_TABLE_PREFIX.'pages',
                self::PAGE_PREFIX,
                sprintf(
                    '%1$s.source_id = %2$s.id AND %1$s.source = "page"',
                    self::DWC_STAT_PREFIX,
                    self::PAGE_PREFIX
                )
            );
        }

        if ($event->usesColumnWithPrefix(self::SEGMENT_PREFIX)) {
            $queryBuilder
                ->leftJoin(
                    self::EMAIL_PREFIX,
                    MAUTIC_TABLE_PREFIX.'email_list_xref',
                    self::SEGMENT_XREF_PREFIX,
                    sprintf('%s.id = %s.email_id',
                        self::EMAIL_PREFIX,
                        self::SEGMENT_XREF_PREFIX
                    )
                )
                ->leftJoin(
                    self::SEGMENT_XREF_PREFIX,
                    MAUTIC_TABLE_PREFIX.'lead_lists',
                    self::SEGMENT_PREFIX,
                    sprintf('%s.leadlist_id = %s.id',
                        self::SEGMENT_XREF_PREFIX,
                        self::SEGMENT_PREFIX
                    )
                );
        }

        $event->addCategoryLeftJoin($queryBuilder, 'dwc');
        $event->addLeadLeftJoin($queryBuilder, 'dwc_stat');

        if (!$event->hasGroupBy()) {
            $queryBuilder->groupBy('dwc_stat.id');
        }

        $event->applyDateFilters($queryBuilder, 'date_sent', self::DWC_STAT_PREFIX);

        $event->setQueryBuilder($queryBuilder);
    }
}
