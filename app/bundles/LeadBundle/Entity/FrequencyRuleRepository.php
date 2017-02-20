<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * FrequecyRuleRepository.
 */
class FrequencyRuleRepository extends CommonRepository
{
    /**
     * @param        $channel
     * @param        $leadIds
     * @param        $defaultFrequencyNumber
     * @param        $defaultFrequencyTime
     * @param string $statTable
     * @param string $statSentColumn
     * @param string $statContactColumn
     *
     * @return array
     */
    public function getAppliedFrequencyRules($channel, $leadIds, $defaultFrequencyNumber, $defaultFrequencyTime, $statTable = 'email_stats', $statContactColumn = 'lead_id', $statSentColumn = 'date_sent')
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $selectFrequency = ($defaultFrequencyNumber) ? 'IFNULL(fr.frequency_number,:defaultNumber) as frequency_number' : 'fr.frequency_number';
        $selectNumber    = ($defaultFrequencyTime) ? 'IFNULL(fr.frequency_time,:frequencyTime) as frequency_time' : 'fr.frequency_time';

        $q->select("ch.$statContactColumn, $selectFrequency, $selectNumber")
            ->from(MAUTIC_TABLE_PREFIX.$statTable, 'ch')
            ->leftJoin('ch', MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr', "ch.{$statContactColumn} = fr.lead_id");

        if ($channel) {
            $q->andWhere('fr.channel = :channel or fr.channel is null')
                ->setParameter('channel', $channel);
        }

        if (!empty($defaultFrequencyTime)) {
            $q->andWhere('ch.'.$statSentColumn.' >= case fr.frequency_time 
                    when \'MONTH\' then DATE_SUB(NOW(),INTERVAL 1 MONTH) 
                    when \'DAY\' then DATE_SUB(NOW(),INTERVAL 1 DAY) 
                    when \'WEEK\' then DATE_SUB(NOW(),INTERVAL 1 WEEK)
                    else DATE_SUB(NOW(),INTERVAL 1 '.$defaultFrequencyTime.')
                    end')
                ->setParameter('frequencyTime', $defaultFrequencyTime);
        } else {
            $q->andWhere('(ch.'.$statSentColumn.' >= case fr.frequency_time
                     when \'MONTH\' then DATE_SUB(NOW(),INTERVAL 1 MONTH)
                     when \'DAY\' then DATE_SUB(NOW(),INTERVAL 1 DAY)
                     when \'WEEK\' then DATE_SUB(NOW(),INTERVAL 1 WEEK)
                    end)');
        }

        if (empty($leadIds)) {
            // Preventative for fetching every single email stat
            $leadIds = [0];
        }
        $q->andWhere(
            $q->expr()->in("ch.$statContactColumn", $leadIds)
        );

        $q->groupBy("ch.$statContactColumn, fr.frequency_time, fr.frequency_number");

        if ($defaultFrequencyNumber != null) {
            $q->having("(count(ch.$statContactColumn) >= IFNULL(fr.frequency_number,:defaultNumber))")
                ->setParameter('defaultNumber', $defaultFrequencyNumber);
        } else {
            $q->having("(count(ch.$statContactColumn) >= fr.frequency_number)");
        }

        $results = $q->execute()->fetchAll();

        return $results;
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

        $results = $q->execute()->fetchAll();

        return $results;
    }
}
