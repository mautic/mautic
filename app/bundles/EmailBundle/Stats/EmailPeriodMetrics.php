<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Stats;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Helper\DateTimeHelper;

final readonly class EmailPeriodMetrics
{
    private const CAMPAIGN_EVENT_SOURCE = 'campaign.event';

    private const EMAIL_SOURCE          = 'email';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param array<int, int> $eventsIds
     *
     * @return array<int, array<string, int|string>>
     *
     * @throws \Exception
     */
    public function emailMetricsPerWeekdayByCampaignEvents(array $eventsIds, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo, string $timezoneOffset): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $daysSubQuery = $this->createDaysSubQuery();

        $queryBuilder
            ->select(
                'd.day',
                'COALESCE(s.sent_count, 0) AS sent_count',
                'COALESCE(r.read_count, 0) AS read_count',
                'COALESCE(c.hit_count, 0) AS hit_count'
            )->from("({$daysSubQuery->getSQL()})", 'd')
            ->leftJoin('d', "({$this->createClicksSubQuery()->getSQL()})", 'c', 'c.hit_day = d.day')
            ->leftJoin('d', "({$this->createSentSubQuery()->getSQL()})", 's', 's.sent_day = d.day')
            ->leftJoin('d', "({$this->createReadSubQuery()->getSQL()})", 'r', 'r.read_day = d.day')
            ->setParameter('source_ids', $eventsIds, ArrayParameterType::INTEGER)
            ->setParameter('timezoneOffset', $timezoneOffset)
            ->setParameter('dateFrom', $dateFrom->format(DateTimeHelper::FORMAT_DB))
            ->setParameter('dateTo', $dateTo->setTime(23, 59, 59)->format(DateTimeHelper::FORMAT_DB))
            ->setParameter('email_source', self::EMAIL_SOURCE)
            ->setParameter('campaign_event_source', self::CAMPAIGN_EVENT_SOURCE)
            ->orderBy('d.day');

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param array<int, int> $eventsIds
     *
     * @return array<int, array<string, int|string>>
     */
    public function emailMetricsPerHourByCampaignEvents(array $eventsIds, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo, string $timezoneOffset): array
    {
        $queryBuilder  = $this->connection->createQueryBuilder();
        $hoursSubQuery = $this->createHoursSubQuery();

        $platform = $this->connection->getDatabasePlatform();
        $queryBuilder->select(
            'h.hour',
            'COALESCE(s.sent_count, 0) AS sent_count',
            'COALESCE(r.read_count, 0) AS read_count',
            'COALESCE(c.hit_count, 0) AS hit_count'
        );

        // make sure value is an integer
        $hitHourCondition  = DatabasePlatform::applyTypeIfStrict($platform, 'c.hit_hour').' + 0 = h.hour';
        $sentHourCondition = DatabasePlatform::applyTypeIfStrict($platform, 's.sent_hour').' + 0 = h.hour';
        $readHourCondition = DatabasePlatform::applyTypeIfStrict($platform, 'r.read_hour').' + 0 = h.hour';

        $queryBuilder
            ->from("({$hoursSubQuery->getSQL()})", 'h')
            ->leftJoin('h', "({$this->createClicksHourlySubQuery()->getSQL()})", 'c', $hitHourCondition)
            ->leftJoin('h', "({$this->createSentHourlySubQuery()->getSQL()})", 's', $sentHourCondition)
            ->leftJoin('h', "({$this->createReadHourlySubQuery()->getSQL()})", 'r', $readHourCondition)
            ->setParameter('source_ids', $eventsIds, ArrayParameterType::INTEGER)
            ->setParameter('timezoneOffset', $timezoneOffset)
            ->setParameter('format', '%H')
            ->setParameter('dateFrom', $dateFrom->format(DateTimeHelper::FORMAT_DB))
            ->setParameter('dateTo', $dateTo->setTime(23, 59, 59)->format(DateTimeHelper::FORMAT_DB))
            ->setParameter('email_source', self::EMAIL_SOURCE)
            ->setParameter('campaign_event_source', self::CAMPAIGN_EVENT_SOURCE)
            ->orderBy('h.hour');

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    private function createClicksSubQuery(): QueryBuilder
    {
        $platform     = $this->connection->getDatabasePlatform();
        $adjustedDate = DatabasePlatform::getOffsetAdjustedDate($platform, 'ph.date_hit');
        $hitDay       = DatabasePlatform::getWeekdayExpression($platform, $adjustedDate);

        return $this->connection->createQueryBuilder()
            ->select(
                $hitDay.' AS hit_day',
                'COUNT(DISTINCT ph.id) AS hit_count'
            )
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
            ->join('es', MAUTIC_TABLE_PREFIX.'page_hits', 'ph', 'es.lead_id = ph.lead_id')
            ->join('es', MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut', 'cut.channel_id = es.email_id AND cut.redirect_id = ph.redirect_id')
            ->where('ph.date_hit BETWEEN :dateFrom AND :dateTo')
            ->andWhere('ph.source = :email_source')
            ->andWhere('cut.channel = :email_source')
            ->andWhere('es.source = :campaign_event_source')
            ->andWhere('es.source_id IN (:source_ids)')
            ->groupBy('hit_day');
    }

    private function createSentSubQuery(): QueryBuilder
    {
        return $this->createBasicStatsSubQuery('date_sent', 'sent_day', 'sent_count');
    }

    private function createReadSubQuery(): QueryBuilder
    {
        return $this->createBasicStatsSubQuery('date_read', 'read_day', 'read_count')
            ->andWhere('es.is_read = 1');
    }

    private function createClicksHourlySubQuery(): QueryBuilder
    {
        $platform     = $this->connection->getDatabasePlatform();
        $adjustedDate = DatabasePlatform::getOffsetAdjustedDate($platform, 'ph.date_hit');
        $hourExpr     = DatabasePlatform::getHourExpression($platform, $adjustedDate);

        return $this->connection->createQueryBuilder()
            ->select(
                "$hourExpr AS hit_hour",
                'COUNT(DISTINCT ph.id) AS hit_count'
            )
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
            ->join('es', MAUTIC_TABLE_PREFIX.'page_hits', 'ph', 'es.lead_id = ph.lead_id')
            ->join('es', MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut', 'cut.channel_id = es.email_id AND cut.redirect_id = ph.redirect_id')
            ->where('ph.date_hit BETWEEN :dateFrom AND :dateTo')
            ->andWhere('ph.source = :email_source')
            ->andWhere('cut.channel = :email_source')
            ->andWhere('es.source = :campaign_event_source')
            ->andWhere('es.source_id IN (:source_ids)')
            ->groupBy('hit_hour')
            ->orderBy('hit_hour', 'ASC');
    }

    private function createSentHourlySubQuery(): QueryBuilder
    {
        return $this->createBasicHourlyStatsSubQuery('date_sent', 'sent_hour', 'sent_count');
    }

    private function createReadHourlySubQuery(): QueryBuilder
    {
        return $this->createBasicHourlyStatsSubQuery('date_read', 'read_hour', 'read_count')
            ->andWhere('es.is_read = 1');
    }

    private function createBasicStatsSubQuery(string $dateColumn, string $groupByAlias, string $countAlias): QueryBuilder
    {
        $platform     = $this->connection->getDatabasePlatform();
        $adjustedDate = DatabasePlatform::getOffsetAdjustedDate($platform, $dateColumn);
        $weekdayExpr  = DatabasePlatform::getWeekdayExpression($platform, $adjustedDate);

        return $this->connection->createQueryBuilder()
            ->select(
                "{$weekdayExpr} AS {$groupByAlias}",
                "COUNT(id) AS {$countAlias}"
            )
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
            ->where("es.{$dateColumn} IS NOT NULL")
            ->andWhere("es.{$dateColumn} BETWEEN :dateFrom AND :dateTo")
            ->andWhere('es.source = :campaign_event_source')
            ->andWhere('es.source_id IN (:source_ids)')
            ->groupBy($groupByAlias)
            ->orderBy($groupByAlias);
    }

    private function createBasicHourlyStatsSubQuery(string $dateColumn, string $groupByAlias, string $countAlias): QueryBuilder
    {
        $platform     = $this->connection->getDatabasePlatform();
        $adjustedDate = DatabasePlatform::getOffsetAdjustedDate($platform, $dateColumn);
        $hourExpr     = DatabasePlatform::getHourExpression($platform, $adjustedDate);

        $qb = $this->connection->createQueryBuilder()
            ->select(
                "{$hourExpr} AS {$groupByAlias}",
                "COUNT(id) AS {$countAlias}"
            )
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
            ->where("es.{$dateColumn} IS NOT NULL")
            ->andWhere("es.{$dateColumn} BETWEEN :dateFrom AND :dateTo")
            ->andWhere('es.source = :campaign_event_source')
            ->andWhere('es.source_id IN (:source_ids)')
            ->groupBy($groupByAlias)
            ->orderBy($groupByAlias, 'ASC');

        return $qb->setMaxResults(24);
    }

    private function createDaysSubQuery(): QueryBuilder
    {
        $subQuery  = $this->connection->createQueryBuilder();
        $daysQuery = '0 AS day';
        for ($i = 1; $i < 7; ++$i) {
            $daysQuery .= sprintf(' UNION ALL SELECT %d AS day', $i);
        }

        return $subQuery->select($daysQuery);
    }

    private function createHoursSubQuery(): QueryBuilder
    {
        $subQuery    = $this->connection->createQueryBuilder();
        $hoursString = '00 AS hour';
        for ($i = 1; $i < 24; ++$i) {
            $hoursString .= sprintf(' UNION ALL SELECT %02d AS hour', $i);
        }

        return $subQuery->select($hoursString);
    }
}
