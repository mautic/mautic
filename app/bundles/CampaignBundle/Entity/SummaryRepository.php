<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\DBALException;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\TimelineTrait;
use PDO;

class SummaryRepository extends CommonRepository
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
        DateTimeInterface $dateFrom = null,
        DateTimeInterface $dateTo = null
    ): array {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select(
                [
                    'cs.event_id',
                    'SUM(cs.scheduled_count) as scheduled_count',
                    'SUM(cs.triggered_count) as triggered_count',
                    'SUM(cs.non_action_path_taken_count) as non_action_path_taken_count',
                    'SUM(cs.failed_count) as failed_count',
                    'SUM(cs.log_counts_processed) as log_counts_processed',
                ]
            )
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary', 'cs')
            ->where('cs.campaign_id = '.(int) $campaignId)
            ->groupBy('cs.event_id');

        if ($dateFrom && $dateTo) {
            $q->andWhere('cs.date_triggered BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
                ->setParameter('dateFrom', $dateFrom->getTimestamp(), PDO::PARAM_INT)
                ->setParameter('dateTo', $dateTo->getTimestamp(), PDO::PARAM_INT);
        }

        $results = $q->execute()->fetchAll();

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
    public function getOldestTriggeredDate(): ?DateTimeInterface
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('cs.date_triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary', 'cs')
            ->orderBy('cs.date_triggered', 'ASC')
            ->setMaxResults(1);

        $results = $qb->execute()->fetchAll();

        return isset($results[0]['date_triggered']) ? new DateTime($results[0]['date_triggered']) : null;
    }

    /**
     * Regenerate summary entries for a given time frame.
     *
     * @throws DBALException
     */
    public function summarize(
        DateTimeInterface $dateFrom,
        DateTimeInterface $dateTo,
        int $campaignId = null,
        int $eventId = null
    ): void {
        $dateFromTs = $dateFrom->getTimestamp();
        $dateToTs   = $dateTo->getTimestamp();

        $sql = 'INSERT INTO '.MAUTIC_TABLE_PREFIX.'campaign_summary '.
            ' (campaign_id, event_id, date_triggered, scheduled_count, non_action_path_taken_count, failed_count, triggered_count, log_counts_processed) '.
            ' SELECT * FROM (SELECT '.
            '       mclel.campaign_id AS campaign_id, '.
            '       mclel.event_id AS event_id, '.
            '       FROM_UNIXTIME(UNIX_TIMESTAMP(mclel.date_triggered) - (UNIX_TIMESTAMP(mclel.date_triggered) % 3600)) AS date_triggered_i, '.
            '       SUM(IF(mclel.is_scheduled = 1 AND mclel.trigger_date > NOW(), 1, 0)) AS scheduled_count_i, '.
            '       SUM(IF(mclel.is_scheduled = 1 AND mclel.trigger_date > NOW(), 0, mclel.non_action_path_taken)) AS non_action_path_taken_count_i, '.
            '       SUM(IF((mclel.is_scheduled = 1 AND mclel.trigger_date > NOW()) OR mclel.non_action_path_taken, 0, mclefl.log_id IS NOT NULL)) AS failed_count_i, '.
            '       SUM(IF((mclel.is_scheduled = 1 AND mclel.trigger_date > NOW()) OR mclel.non_action_path_taken OR mclefl.log_id IS NOT NULL, 0, 1)) AS triggered_count_i, '.
            '       (SELECT count(mclel2.lead_id) FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log mclel2 '.
            '           INNER JOIN '.MAUTIC_TABLE_PREFIX.'campaign_leads mcl ON mcl.campaign_id = mclel2.campaign_id AND mcl.manually_removed = 0 '.
            '           AND mclel2.lead_id = mcl.lead_id AND mcl.rotation = mclel2.rotation '.
            '           WHERE mclel2.campaign_id = mclel.campaign_id AND mclel2.event_id = mclel.event_id AND '.
            '               NOT EXISTS(SELECT NULL FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log mclefl2 '.
            '               WHERE mclefl2.log_id = mclel2.id AND mclefl2.date_added BETWEEN FROM_UNIXTIME('.$dateFromTs.') AND FROM_UNIXTIME('.$dateToTs.')) AND '.
            '               mclel2.date_triggered BETWEEN FROM_UNIXTIME('.$dateFromTs.') AND FROM_UNIXTIME('.$dateToTs.') '.
            '       ) AS log_counts_processed_i '.
            ' FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log mclel LEFT JOIN '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log mclefl ON mclefl.log_id = mclel.id '.
            ' WHERE (mclel.date_triggered BETWEEN FROM_UNIXTIME('.$dateFromTs.') AND FROM_UNIXTIME('.$dateToTs.')) ';

        if ($campaignId) {
            $sql .= ' AND mclel.campaign_id = '.$campaignId;
        }

        if ($eventId) {
            $sql .= ' AND mclel.event_id = '.$eventId;
        }

        $sql .= ' GROUP BY mclel.campaign_id, mclel.event_id, date_triggered_i) AS `s` '.
            ' ON DUPLICATE KEY UPDATE '.
            ' scheduled_count = s.scheduled_count_i, '.
            ' non_action_path_taken_count = s.non_action_path_taken_count_i, '.
            ' failed_count = s.failed_count_i, '.
            ' triggered_count = s.triggered_count_i, '.
            ' log_counts_processed = s.log_counts_processed_i;';

        $this->getEntityManager()->getConnection()->query($sql);
    }
}
