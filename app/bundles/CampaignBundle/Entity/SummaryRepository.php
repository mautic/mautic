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
     * @return bool|void
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function saveEntities($entities)
    {
        $values = [];
        foreach ($entities as $summary) {
            /** @var $summary Summary */
            $timeStamp = $summary->getDateTriggered()->getTimestamp();
            $values[]  = implode(
                ',',
                [
                    $summary->getCampaign()->getId(),
                    $summary->getEvent()->getId(),
                    'FROM_UNIXTIME('.$timeStamp.')',
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
     * @param $options
     *
     * @return array
     */
    public function getChartQuery($options)
    {
        // @todo - Create alternative to the standard LeadEventLogRepository
    }
}
