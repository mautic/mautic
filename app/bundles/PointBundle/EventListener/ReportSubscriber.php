<?php

namespace Mautic\PointBundle\EventListener;

use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\LeagueContactScore;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    public const CONTEXT_LEAGUE_SCORE = 'league.score';
    public const LEAGUE_PREFIX        = 'pl';
    public const LEAGUE_SCORE_PREFIX  = 'ls';

    public const LEAGUE_COLUMNS = [
        self::LEAGUE_PREFIX.'.id' => [
            'label' => 'mautic.point.report.league_id',
            'type'  => 'int',
        ],
        self::LEAGUE_PREFIX.'.name' => [
            'label' => 'mautic.point.report.league_name',
            'type'  => 'string',
        ],
        self::LEAGUE_SCORE_PREFIX.'.score' => [
            'label' => 'mautic.point.report.league_score',
            'type'  => 'int',
        ],
    ];

    private array $reportContexts = [
        self::CONTEXT_LEAGUE_SCORE,
    ];

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    public function __construct(FieldsBuilder $fieldsBuilder)
    {
        $this->fieldsBuilder  = $fieldsBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', -10],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', -10],
        ];
    }

    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if (!$event->checkContext($this->reportContexts)) {
            return;
        }

        if ($event->checkContext(self::CONTEXT_LEAGUE_SCORE)) {
            $columns = array_merge(
                self::LEAGUE_COLUMNS,
                $event->getLeadColumns()
            );
            $filters = array_merge(
                $columns,
                $this->fieldsBuilder->getLeadFilter('l.', 's.')
            );
            $data = [
                'display_name' => 'mautic.point.league.report.table',
                'columns'      => $columns,
                'filters'      => $filters,
            ];
            $event->addTable(self::CONTEXT_LEAGUE_SCORE, $data, 'contacts');
        }
    }

    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        if (!$event->checkContext($this->reportContexts)) {
            return;
        }

        $qb = $event->getQueryBuilder();

        if ($event->checkContext(self::CONTEXT_LEAGUE_SCORE)) {
            $qb->from(MAUTIC_TABLE_PREFIX.LeagueContactScore::TABLE_NAME, self::LEAGUE_SCORE_PREFIX)
                ->leftJoin(self::LEAGUE_SCORE_PREFIX, MAUTIC_TABLE_PREFIX.League::TABLE_NAME, self::LEAGUE_PREFIX, self::LEAGUE_SCORE_PREFIX.'.league_id = '.self::LEAGUE_PREFIX.'.id')
                ->leftJoin(self::LEAGUE_SCORE_PREFIX, MAUTIC_TABLE_PREFIX.'leads', 'l', self::LEAGUE_SCORE_PREFIX.'.contact_id = l.id');

            if ($event->hasFilter('s.leadlist_id')) {
                $qb->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 's', 's.lead_id = l.id AND s.manually_removed = 0');
            }
        }

        $event->setQueryBuilder($qb);
    }
}
