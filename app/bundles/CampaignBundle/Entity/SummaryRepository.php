<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * SummaryRepository.
 */
class SummaryRepository extends CommonRepository
{
    use TimelineTrait;
    use ContactLimiterTrait;

    /**
     * @return string
     */
    public function getTableAlias()
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
    public function saveEntities($entities)
    {
        $values = [];
        foreach ($entities as $summary) {
            /* @var $summary Summary */
            $values[]  = implode(
                ',',
                [
                    $summary->getCampaign()->getId(),
                    $summary->getEvent()->getId(),
                    'FROM_UNIXTIME('.$summary->getDateTriggered()->getTimestamp().')',
                    $summary->getScheduledCount(),
                    $summary->getTriggeredCount(),
                    $summary->getNonActionPathTakenCount(),
                    $summary->getFailedCount(),
                ]
            );
        }

        $query = 'INSERT INTO '.MAUTIC_TABLE_PREFIX.'campaign_summary '.
            '(campaign_id, event_id, date_triggered, scheduled_count, triggered_count, non_action_path_taken_count, failed_count) '.
            'VALUES ('.implode('),(', $values).') '.
            'ON DUPLICATE KEY UPDATE '.
            'scheduled_count=scheduled_count+VALUES(scheduled_count), '.
            'triggered_count=triggered_count+VALUES(triggered_count), '.
            'non_action_path_taken_count=non_action_path_taken_count+VALUES(non_action_path_taken_count), '.
            'failed_count=failed_count+VALUES(failed_count) ';

        $this->getEntityManager()
            ->getConnection()
            ->prepare($query)
            ->execute();

        $this->getEntityManager()->flush();
    }

    /**
     * @param      $campaignId
     * @param null $dateRangeValues
     *
     * @return array
     */
    public function getCampaignLogCounts(
        $campaignId,
        $dateRangeValues = null
    ) {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select(
                'cs.event_id, SUM(cs.scheduled_count) as scheduled_count, SUM(cs.triggered_count) as triggered_count, SUM(cs.non_action_path_taken_count) as non_action_path_taken_count, SUM(cs.failed_count) as failed_count'
            )
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary', 'cs')
            ->where('cs.campaign_id = '.(int) $campaignId)
            ->groupBy('cs.event_id');

        if (!empty($dateRangeValues)) {
            $dateFrom = new DateTimeHelper($dateRangeValues['date_from']);
            $dateTo   = new DateTimeHelper($dateRangeValues['date_to']);
            $q->andWhere(
                $q->expr()->gte('cs.date_triggered', ':dateFrom'),
                $q->expr()->lte('cs.date_triggered', ':dateTo')
            );
            $q->setParameter('dateFrom', $dateFrom->toUtcString());
            $q->setParameter('dateTo', $dateTo->toUtcString());
        }

        $results = $q->execute()->fetchAll();

        $return = [];
        // Group by event id
        foreach ($results as $row) {
            $return[$row['event_id']] = [
                0 => (int) $row['non_action_path_taken_count'],
                1 => (int) $row['triggered_count'],
            ];
        }

        return $return;
    }

    /**
     * Get the oldest triggered time for back-filling historical data.
     *
     * @return \DateTime
     */
    public function getOldestTriggered()
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('cs.date_triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary', 'cs')
            ->orderBy('cs.date_triggered', 'ASC')
            ->setMaxResults(1);

        $results = $q->execute()->fetchAll();

        return new \DateTime(isset($results[0]['date_triggered']) ? $results[0]['date_triggered'] : null);
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return array
     */
    public function dropRange(\DateTime $dateFrom, \DateTime $dateTo)
    {
        return $this->_em->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'campaign_summary')
            ->where('date_triggered BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
            ->setParameter('dateFrom', $dateFrom->getTimestamp())
            ->setParameter('dateTo', $dateTo->getTimestamp())
            ->execute()
            ->fetchAll();
    }

    /**
     * Regenerate summary entries for a given time frame.
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int       $limit
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function regenerate(\DateTime $dateFrom, \DateTime $dateTo, $limit = 1)
    {
        $query = 'INSERT INTO '.MAUTIC_TABLE_PREFIX.'campaign_summary '.
            '(campaign_id, event_id, date_triggered, scheduled_count, triggered_count, non_action_path_taken_count, failed_count) '.
            '(
                SELECT FROM_UNIXTIMESTAMP(CAST((UNIX_TIMESTAMP(t.date_triggered) / 3600) AS UNSIGNED INTEGER)) AS date_triggered, COUNT(*) AS count
                FROM campaign_lead_event_log t AND (NOT EXISTS (SELECT null FROM campaign_lead_event_failed_log fe WHERE fe.log_id = t.id)) AND (t.date_triggered BETWEEN \'2018-08-13 00:00:00\' AND \'2018-09-13 16:19:46\') GROUP BY DATE_FORMAT(t.date_triggered, \'%Y-%m-%d\') ORDER BY DATE_FORMAT(t.date_triggered, \'%Y-%m-%d\') ASC LIMIT 32;
             ) '.
            'ON DUPLICATE KEY UPDATE '.
            'scheduled_count=scheduled_count+VALUES(scheduled_count), '.
            'triggered_count=triggered_count+VALUES(triggered_count), '.
            'non_action_path_taken_count=non_action_path_taken_count+VALUES(non_action_path_taken_count), '.
            'failed_count=failed_count+VALUES(failed_count) ';

        $this->getEntityManager()
            ->getConnection()
            ->prepare($query)
            ->execute();
    }
}
