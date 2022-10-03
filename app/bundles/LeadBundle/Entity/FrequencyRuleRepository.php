<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * FrequecyRuleRepository.
 */
class FrequencyRuleRepository extends CommonRepository
{
    /**
     * @param string      $channel
     * @param array       $leadIds
     * @param string|null $defaultFrequencyNumber
     * @param string|null $defaultFrequencyTime
     * @param string      $statTable
     * @param string      $statSentColumn
     * @param string      $statContactColumn
     *
     * @return array
     */
    public function getAppliedFrequencyRules(
        $channel,
        $leadIds,
        $defaultFrequencyNumber,
        $defaultFrequencyTime,
        $statTable = 'email_stats',
        $statContactColumn = 'lead_id',
        $statSentColumn = 'date_sent'
    ) {
        if (empty($leadIds)) {
            return [];
        }

        $violations = $this->getCustomFrequencyRuleViolations($channel, $leadIds, $statTable, $statContactColumn, $statSentColumn);

        if ($defaultFrequencyNumber && $defaultFrequencyTime) {
            $violations = array_merge(
                $violations,
                $this->getDefaultFrequencyRuleViolations(
                    $leadIds,
                    $defaultFrequencyNumber,
                    $defaultFrequencyTime,
                    $statTable,
                    $statContactColumn,
                    $statSentColumn
                )
            );
        }

        return $violations;
    }

    /**
     * @param null $channel
     * @param null $leadIds
     *
     * @return array
     */
    public function getFrequencyRules($channel = null, $leadIds = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select(
            'fr.id, fr.frequency_time, fr.frequency_number, fr.channel, fr.preferred_channel, fr.pause_from_date, fr.pause_to_date, fr.lead_id'
        )
            ->from(MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr');

        if ($channel) {
            $q->andWhere('fr.channel = :channel')
                ->setParameter('channel', $channel);
        }

        $groupByLeads = is_array($leadIds);
        if ($leadIds) {
            if ($groupByLeads) {
                $q->andWhere(
                    $q->expr()->in('fr.lead_id', $leadIds)
                );
            } else {
                $q->andWhere('fr.lead_id = :leadId')
                    ->setParameter('leadId', (int) $leadIds);
            }
        }

        $results = $q->execute()->fetchAll();

        $frequencyRules = [];

        foreach ($results as $result) {
            if ($groupByLeads) {
                if (!isset($frequencyRules[$result['lead_id']])) {
                    $frequencyRules[$result['lead_id']] = [];
                }

                $frequencyRules[$result['lead_id']][$result['channel']] = $result;
            } else {
                $frequencyRules[$result['channel']] = $result;
            }
        }

        return $frequencyRules;
    }

    /**
     * @param $leadId
     *
     * @return array
     */
    public function getPreferredChannel($leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('fr.id, fr.frequency_time, fr.frequency_number, fr.channel, fr.pause_from_date, fr.pause_to_date')
            ->from(MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr');
        $q->where('fr.preferred_channel = :preferredChannel')
            ->setParameter('preferredChannel', true, 'boolean');
        if ($leadId) {
            $q->andWhere('fr.lead_id = :leadId')
                ->setParameter('leadId', $leadId);
        }

        return $q->execute()->fetchAll();
    }

    /**
     * @param string $channel
     * @param string $statTable
     * @param string $statContactColumn
     * @param string $statSentColumn
     *
     * @return array
     */
    private function getCustomFrequencyRuleViolations($channel, array $leadIds, $statTable, $statContactColumn, $statSentColumn)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select("ch.$statContactColumn, fr.frequency_number, fr.frequency_time")
            ->from(MAUTIC_TABLE_PREFIX.$statTable, 'ch')
            ->join('ch', MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr', "ch.{$statContactColumn} = fr.lead_id");

        if ($channel) {
            $q->andWhere('fr.channel = :channel')
                ->setParameter('channel', $channel);
        }

        // Preferred channel is stored in this table so they may not have a frequency rule defined but just a preference so exclude them
        $q->andWhere('fr.frequency_time IS NOT NULL AND fr.frequency_number IS NOT NULL');

        // Calculate the rule timeframe
        $q->andWhere(
            '(ch.'.$statSentColumn.' >= case fr.frequency_time
                 when \'MONTH\' then DATE_SUB(NOW(),INTERVAL 1 MONTH)
                 when \'DAY\' then DATE_SUB(NOW(),INTERVAL 1 DAY)
                 when \'WEEK\' then DATE_SUB(NOW(),INTERVAL 1 WEEK)
                end)'
        );

        $q->andWhere(
            $q->expr()->in("ch.$statContactColumn", $leadIds)
        );

        $q->groupBy("ch.$statContactColumn, fr.frequency_time, fr.frequency_number");

        $q->having("count(ch.$statContactColumn) >= fr.frequency_number");

        return $q->execute()->fetchAll();
    }

    /**
     * @param string $defaultFrequencyNumber
     * @param string $defaultFrequencyTime
     * @param string $statTable
     * @param string $statContactColumn
     * @param string $statSentColumn
     *
     * @return array
     */
    private function getDefaultFrequencyRuleViolations(
        array $leadIds,
        $defaultFrequencyNumber,
        $defaultFrequencyTime,
        $statTable,
        $statContactColumn,
        $statSentColumn
    ) {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select("ch.$statContactColumn")
            ->from(MAUTIC_TABLE_PREFIX.$statTable, 'ch');

        switch ($defaultFrequencyTime) {
            case 'MONTH':
                $since = new \DateTime('-1 month', new \DateTimeZone('UTC'));
                break;
            case 'WEEK':
                $since = new \DateTime('-1 week', new \DateTimeZone('UTC'));
                break;
            case 'DAY':
                $since = new \DateTime('-1 day', new \DateTimeZone('UTC'));
                break;
            default:
                return [];
        }

        $q->andWhere('ch.'.$statSentColumn.' >= :frequencyTime')
            ->setParameter('frequencyTime', $since->format('Y-m-d H:i:s'));

        $q->andWhere(
            $q->expr()->in("ch.$statContactColumn", $leadIds)
        );

        // Exclude contacts with custom rules defined
        $subQuery = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $subQuery->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr')
            ->where("fr.lead_id = ch.{$statContactColumn}")
            ->andWhere('fr.frequency_time IS NOT NULL AND fr.frequency_number IS NOT NULL');
        $q->andWhere(
            sprintf('NOT EXISTS (%s)', $subQuery->getSQL())
        );

        $q->groupBy("ch.$statContactColumn");

        $q->having("count(ch.$statContactColumn) >= :defaultNumber")
            ->setParameter('defaultNumber', $defaultFrequencyNumber);

        $results = $q->execute()->fetchAll();
        foreach ($results as $key => $result) {
            $results[$key]['frequency_number'] = $defaultFrequencyNumber;
            $results[$key]['frequency_time']   = $defaultFrequencyTime;
        }

        return $results;
    }
}
