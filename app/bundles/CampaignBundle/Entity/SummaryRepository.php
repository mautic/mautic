<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity;

use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * @extends CommonRepository<Summary>
 */
final class SummaryRepository extends CommonRepository
{
    use TimelineTrait;
    use ContactLimiterTrait;

    public function getTableAlias(): string
    {
        return 's';
    }

    /**
     * @return array<int|string, array<int|string, int|string>>
     */
    public function getCampaignLogCounts(
        int $campaignId,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
    ): array {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select(
                'cs.event_id',
                'SUM(cs.scheduled_count) as scheduled_count',
                'SUM(cs.triggered_count) as triggered_count',
                'SUM(cs.non_action_path_taken_count) as non_action_path_taken_count',
                'SUM(cs.failed_count) as failed_count',
                'SUM(cs.log_counts_processed) as log_counts_processed',
            )
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary', 'cs')
            ->where('cs.campaign_id = '.$campaignId)
            ->groupBy('cs.event_id');

        if ($dateFrom && $dateTo) {
            $q->andWhere('cs.date_triggered BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
                ->setParameter('dateFrom', $dateFrom->getTimestamp(), \PDO::PARAM_INT)
                ->setParameter('dateTo', $dateTo->getTimestamp(), \PDO::PARAM_INT);
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        $return = [];
        // Group by event id
        foreach ($results as $row) {
            $return[$row['event_id']] = [
                0 => (int) $row['non_action_path_taken_count'],
                1 => (int) $row['triggered_count'] + (int) $row['scheduled_count'],
                2 => (int) $row['log_counts_processed'],
            ];
        }

        return $return;
    }

    /**
     * Get the oldest triggered time for back-filling historical data.
     */
    public function getOldestTriggeredDate(): ?\DateTimeInterface
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('cs.date_triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary', 'cs')
            ->orderBy('cs.date_triggered', 'ASC')
            ->setMaxResults(1);

        $results = $qb->executeQuery()->fetchAllAssociative();

        return isset($results[0]['date_triggered']) ? new \DateTime($results[0]['date_triggered']) : null;
    }

    /**
     * Regenerate summary entries for a given time frame.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function summarize(
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $campaignId = null,
        ?int $eventId = null,
    ): void {
        $dateFromTsActual  = $dateFrom->getTimestamp();
        $dateToTsActual    = $dateTo->getTimestamp();
        $intervalInSeconds = 3600;

        $dateFromStartWithZeroMinutes = $dateFromTsActual - ($dateFromTsActual % $intervalInSeconds);
        $numberOfIntervals            = ceil(($dateToTsActual - $dateFromStartWithZeroMinutes) / $intervalInSeconds);

        $connection = $this->getEntityManager()->getConnection();
        $platform   = $connection->getDatabasePlatform();

        // Get ID handling for PostgreSQL (nextval) vs MySQL (auto_increment)
        $idHandling = DatabasePlatform::getInsertIdHandling(
            $platform,
            MAUTIC_TABLE_PREFIX.'campaign_summary',
            $this->getEntityManager()->getClassMetadata(Summary::class)
        );

        for ($interval = 0; $interval < $numberOfIntervals; ++$interval) {
            $dateFromTs = date('Y-m-d H:i:s', $dateFromStartWithZeroMinutes + ($interval * $intervalInSeconds));
            $dateToTs   = date('Y-m-d H:i:s', strtotime($dateFromTs) + ($intervalInSeconds - 1));

            // Quote the date strings once
            $quotedFrom = $connection->quote($dateFromTs);
            $quotedTo   = $connection->quote($dateToTs);

            // Platform-specific expressions to ensure correct type (timestamp/datetime)
            $dateTriggeredExpr = DatabasePlatform::applyTypeIfStrict($platform, $quotedFrom, 'timestamp');
            $dateFromExpr      = $dateTriggeredExpr;
            $dateToExpr        = DatabasePlatform::applyTypeIfStrict($platform, $quotedTo, 'timestamp');

            // Build inner aggregation query with consistent integer types in CASE branches
            $innerSql = '
                SELECT
                    '.$idHandling['idSelect'].'
                    mclel.campaign_id AS campaign_id,
                    mclel.event_id AS event_id,
                    '.$dateTriggeredExpr.' AS date_triggered,
                    SUM(CASE WHEN mclel.is_scheduled = 1 AND mclel.trigger_date > NOW() THEN 1 ELSE 0 END) AS scheduled_count,
                    SUM(CASE WHEN mclel.is_scheduled = 1 AND mclel.trigger_date > NOW() THEN 0
                             ELSE CASE WHEN mclel.non_action_path_taken = TRUE THEN 1 ELSE 0 END END) AS non_action_path_taken_count,
                    SUM(CASE WHEN (mclel.is_scheduled = 1 AND mclel.trigger_date > NOW()) OR mclel.non_action_path_taken = TRUE THEN 0
                             ELSE CASE WHEN mclefl.log_id IS NOT NULL THEN 1 ELSE 0 END END) AS failed_count,
                    SUM(CASE WHEN (mclel.is_scheduled = 1 AND mclel.trigger_date > NOW()) OR mclel.non_action_path_taken = TRUE OR mclefl.log_id IS NOT NULL THEN 0
                             ELSE 1 END) AS triggered_count,
                    SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'campaign_leads mcl
                        WHERE mcl.campaign_id = mclel.campaign_id AND mcl.lead_id = mclel.lead_id
                    ) AND mclel.is_scheduled = 0
                    AND mclel.date_triggered IS NOT NULL
                    AND mclefl.log_id IS NULL THEN 1 ELSE 0 END) AS log_counts_processed
                FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log mclel
                LEFT JOIN '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log mclefl
                    ON mclefl.log_id = mclel.id
                   AND mclefl.date_added BETWEEN '.$dateFromExpr.' AND '.$dateToExpr.'
                WHERE mclel.date_triggered BETWEEN '.$dateFromExpr.' AND '.$dateToExpr.'
            ';

            if (null !== $campaignId) {
                $innerSql .= ' AND mclel.campaign_id = '.(int) $campaignId;
            }

            if (null !== $eventId) {
                $innerSql .= ' AND mclel.event_id = '.(int) $eventId;
            }

            $innerSql .= ' GROUP BY mclel.campaign_id, mclel.event_id';

            // Columns for INSERT
            $columns = [
                'campaign_id',
                'event_id',
                'date_triggered',
                'scheduled_count',
                'non_action_path_taken_count',
                'failed_count',
                'triggered_count',
                'log_counts_processed',
            ];

            // Add id column if exists
            if (!empty($idHandling['idColumn'])) {
                array_unshift($columns, $idHandling['idColumn']);
            }

            // Build upsert using centralized helper
            $sql = DatabasePlatform::getSummarizeUpsertStatement(
                $platform,
                MAUTIC_TABLE_PREFIX.'campaign_summary',
                implode(', ', $columns),
                $innerSql,
                'campaign_id, event_id, date_triggered',
                [
                    'scheduled_count'             => 'scheduled_count',
                    'non_action_path_taken_count' => 'non_action_path_taken_count',
                    'failed_count'                => 'failed_count',
                    'triggered_count'             => 'triggered_count',
                    'log_counts_processed'        => 'log_counts_processed',
                ]
            );

            $connection->executeStatement($sql);
        }
    }
}
