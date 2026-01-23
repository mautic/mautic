<?php

namespace Mautic\PointBundle\EventListener;

use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    public const CONTEXT_GROUP_SCORE = 'group.score';

    public const GROUP_PREFIX        = 'pl';

    public const GROUP_SCORE_PREFIX  = 'ls';

    public const GROUP_COLUMNS = [
        self::GROUP_PREFIX.'.id' => [
            'alias' => 'group_id',
            'label' => 'mautic.point.report.group_id',
            'type'  => 'int',
        ],
        self::GROUP_PREFIX.'.name' => [
            'alias' => 'group_name',
            'label' => 'mautic.point.report.group_name',
            'type'  => 'string',
        ],
        self::GROUP_SCORE_PREFIX.'.score' => [
            'alias' => 'group_score',
            'label' => 'mautic.point.report.group_score',
            'type'  => 'int',
        ],
    ];

    /**
     * @var string[]
     */
    private array $reportContexts = [
        self::CONTEXT_GROUP_SCORE,
    ];

    public function __construct(
        private FieldsBuilder $fieldsBuilder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', -10],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', -10],
        ];
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext($this->reportContexts)) {
            return;
        }

        if ($event->checkContext(self::CONTEXT_GROUP_SCORE)) {
            $columns = array_merge(
                self::GROUP_COLUMNS,
                $event->getLeadColumns()
            );
            $filters = array_merge(
                $columns,
                $this->fieldsBuilder->getLeadFilter('l.', 's.')
            );
            $data = [
                'display_name' => 'mautic.point.group.report.table',
                'columns'      => $columns,
                'filters'      => $filters,
            ];
            $event->addTable(self::CONTEXT_GROUP_SCORE, $data, 'contacts');
        }
    }

    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext($this->reportContexts)) {
            return;
        }

        $qb = $event->getQueryBuilder();

        if ($event->checkContext(self::CONTEXT_GROUP_SCORE)) {
            $qb->from(MAUTIC_TABLE_PREFIX.GroupContactScore::TABLE_NAME, self::GROUP_SCORE_PREFIX)
                ->leftJoin(self::GROUP_SCORE_PREFIX, MAUTIC_TABLE_PREFIX.Group::TABLE_NAME, self::GROUP_PREFIX, self::GROUP_SCORE_PREFIX.'.group_id = '.self::GROUP_PREFIX.'.id')
                ->leftJoin(self::GROUP_SCORE_PREFIX, MAUTIC_TABLE_PREFIX.'leads', 'l', self::GROUP_SCORE_PREFIX.'.contact_id = l.id');

            if ($event->hasFilter('s.leadlist_id')) {
                $qb->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 's', 's.lead_id = l.id AND s.manually_removed = 0');
            }
        }

        $event->setQueryBuilder($qb);
    }
}
