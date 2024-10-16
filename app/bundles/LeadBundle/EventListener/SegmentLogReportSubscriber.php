<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentLogReportSubscriber implements EventSubscriberInterface
{
    public const SEGMENT_LOG = 'segment.log';

    public function __construct(
        private FieldsBuilder $fieldsBuilder
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     */
    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext([self::SEGMENT_LOG])) {
            return;
        }

        $columns = $this->fieldsBuilder->getLeadFieldsColumns('l.');

        $filters                        = $this->fieldsBuilder->getLeadFilter('l.', 'log_added.');
        $filters['log_added.object_id'] = $filters['log_added.leadlist_id'];
        unset($filters['log_added.leadlist_id']);

        $segmentColumns = [
            'log_added.object_id' => [
                'label' => 'mautic.lead.report.segment.id',
                'type'  => 'int',
            ],
            'log_added.date_added' => [
                'label' => 'mautic.lead.report.segment.date_added',
                'type'  => 'datetime',
            ],
            'log_removed.date_added' => [
                'label' => 'mautic.lead.report.segment.date_removed',
                'type'  => 'datetime',
            ],
        ];

        $data = [
            'display_name' => 'mautic.lead.report.segment.log',
            'columns'      => array_merge($columns, $segmentColumns),
            'filters'      => array_merge($segmentColumns, $filters),
        ];
        $event->addTable(self::SEGMENT_LOG, $data, ReportSubscriber::GROUP_CONTACTS);

        unset($columns, $filters, $segmentColumns, $data);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext([self::SEGMENT_LOG])) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_event_log', 'log_added', $this->generateLeftJoinCondition('log_added', 'added'))
            ->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_event_log', 'log_removed', $this->generateLeftJoinCondition('log_removed', 'removed').' AND log_removed.object_id = log_added.object_id ');

        $qb->andWhere(
            $qb->expr()->or(
                $qb->expr()->isNotNull('log_added.date_added'),
                $qb->expr()->isNotNull('log_removed.date_added')
            )
        );

        $dateFrom = $event->getOptions()['dateFrom'] ?? (new \DateTime('-30 days'));
        $dateTo   = $event->getOptions()['dateTo'] ?? (new \DateTime());

        $qb->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
        $qb->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));

        if (!$event->hasGroupBy()) {
            $qb->groupBy('l.id,log_added.object_id');
        }

        if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
            $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
        }
        if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
            $event->addLeadIpAddressLeftJoin($qb);
        }

        $event->setQueryBuilder($qb);
    }

    /**
     * @param string $alias
     * @param string $action
     */
    private function generateLeftJoinCondition($alias, $action): string
    {
        return 'l.id = '.$alias.'.lead_id  AND '.$alias.'.bundle = \'lead\' AND '.$alias.'.object = \'segment\'  AND '.$alias.'.`action` =\''.$action.'\' AND '.$alias.'.date_added BETWEEN :dateFrom AND :dateTo';
    }
}
