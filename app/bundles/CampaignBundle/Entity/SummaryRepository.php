<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\FetchMode;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\TimelineTrait;

class SummaryRepository extends CommonRepository
{
    use TimelineTrait;
    use ContactLimiterTrait;

    public function getTableAlias(): string
    {
        return 's';
    }

    /**
     * Insert or update to increment existing rows with a single query.
     *
     * @param array $entities
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveEntities($entities): void
    {
        $values = [];
        foreach ($entities as $summary) {
            /* @var $summary Summary */
            $values[] = implode(
                ',',
                [
                    $summary->getCampaign()->getId(),
                    $summary->getEvent()->getId(),
                    'FROM_UNIXTIME('.$summary->getDateTriggered()->getTimestamp().')',
                    $summary->getScheduledCount(),
                    $summary->getTriggeredCount(),
                    $summary->getNonActionPathTakenCount(),
                    $summary->getFailedCount(),
                    $summary->getLogCountsProcessed(),
                ]
            );
        }

        $sql = 'INSERT INTO '.MAUTIC_TABLE_PREFIX.'campaign_summary '.
            '(campaign_id, event_id, date_triggered, scheduled_count, triggered_count, non_action_path_taken_count, failed_count, log_counts_processed) '.
            'VALUES ('.implode('),(', $values).') '.
            'ON DUPLICATE KEY UPDATE '.
            'scheduled_count = scheduled_count + VALUES(scheduled_count), '.
            'triggered_count = triggered_count + VALUES(triggered_count), '.
            'non_action_path_taken_count = non_action_path_taken_count + VALUES(non_action_path_taken_count), '.
            'failed_count = failed_count + VALUES(failed_count), '.
            'log_counts_processed = VALUES(log_counts_processed);';

        $this->getEntityManager()
            ->getConnection()
            ->prepare($sql)
            ->execute();
    }

    public function getCampaignLogCounts(
        int $campaignId,
        \DateTimeInterface $dateFrom = null,
        \DateTimeInterface $dateTo = null
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
                ->setParameter('dateFrom', $dateFrom->getTimestamp(), \PDO::PARAM_INT)
                ->setParameter('dateTo', $dateTo->getTimestamp(), \PDO::PARAM_INT);
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
    public function getOldestTriggeredDate(): ?\DateTime
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('cs.date_triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary', 'cs')
            ->orderBy('cs.date_triggered', 'ASC')
            ->setMaxResults(1);

        $results = $qb->execute()->fetchAll();

        return isset($results[0]['date_triggered']) ? new \DateTime($results[0]['date_triggered']) : null;
    }

    /**
     * Regenerate summary entries for a given time frame.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function summarize(\DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): void
    {
        $dateFromTs = $dateFrom->getTimestamp();
        $dateToTs   = $dateTo->getTimestamp();

        $sql = 'INSERT INTO '.MAUTIC_TABLE_PREFIX.'campaign_summary '.
            '(campaign_id, event_id, date_triggered, scheduled_count, non_action_path_taken_count, failed_count, triggered_count) '.
            '    SELECT * FROM (SELECT '.
            '        t.campaign_id as campaign_id, '.
            '        t.event_id as event_id, '.
            '        FROM_UNIXTIME(UNIX_TIMESTAMP(t.date_triggered) - (UNIX_TIMESTAMP(t.date_triggered) % 3600)) AS date_triggered_i, '.
            '        SUM(IF(t.is_scheduled = 1 AND t.trigger_date > NOW(), 1, 0)) as scheduled_count_i, '.
            '        SUM(IF(t.is_scheduled = 1 AND t.trigger_date > NOW(), 0, t.non_action_path_taken)) as non_action_path_taken_count_i, '.
            '        SUM(IF((t.is_scheduled = 1 AND t.trigger_date > NOW()) OR t.non_action_path_taken, 0, fe.log_id IS NOT NULL)) as failed_count_i, '.
            '        SUM(IF((t.is_scheduled = 1 AND t.trigger_date > NOW()) OR t.non_action_path_taken OR fe.log_id IS NOT NULL, 0, 1)) as triggered_count_i '.
            '    FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log t '.
            '    LEFT JOIN '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log fe '.
            '        ON fe.log_id = t.id '.
            '    WHERE (t.date_triggered BETWEEN FROM_UNIXTIME('.$dateFromTs.') AND FROM_UNIXTIME('.$dateToTs.')) '.
            '    GROUP BY t.campaign_id, t.event_id, date_triggered_i) AS `s` '.
            'ON DUPLICATE KEY UPDATE '.
            'id = last_insert_id(id), '.
            'scheduled_count = scheduled_count + s.scheduled_count_i, '.
            'non_action_path_taken_count = non_action_path_taken_count + s.non_action_path_taken_count_i, '.
            'failed_count = failed_count + s.failed_count_i, '.
            'triggered_count = triggered_count + s.triggered_count_i, '.
            'log_counts_processed = null;';

        $this->getEntityManager()->getConnection()->query($sql);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteAll(): void
    {
        $sql  = 'DELETE FROM '.MAUTIC_TABLE_PREFIX.'campaign_summary';
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }

    public function updateLogCountsProcessed(array $updateSummaries): void
    {
        foreach ($updateSummaries as $summary) {
            $this->saveEntity($summary);
        }
    }

    public function getLogCountsProcessedCount(): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('count(cs.id) count, max(cs.id) maxId, min(cs.id) minId')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary', 'cs')
            ->where(
                $qb->expr()->isNull('cs.log_counts_processed')
            );

        return $qb->execute()->fetch(FetchMode::ASSOCIATIVE);
    }

    public function getLogCountsProcessedSummaries(array $batchLimiters): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cs')
            ->from('MauticCampaignBundle:Summary', 'cs', 'cs.id')
            ->where(
                $qb->expr()->isNull('cs.logCountsProcessed'),
                $qb->expr()->gte('cs.id', ':minId'),
                $qb->expr()->lte('cs.id', ':maxId')
            )
            ->setParameters(['minId' => $batchLimiters['minId'], 'maxId' => $batchLimiters['maxId']])
            ->setMaxResults($batchLimiters['limit']);

        return $qb->getQuery()->getResult();
    }
}
