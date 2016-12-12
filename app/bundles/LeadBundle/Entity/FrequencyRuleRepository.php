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
     * @param $channel
     * @param $leadIds
     * @param $listId
     * @param $defaultFrequencyNumber
     * @param $defaultFrequencyTime
     *
     * @return array
     */
    public function getAppliedFrequencyRules($channel, $leadIds, $defaultFrequencyNumber, $defaultFrequencyTime)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $selectFrequency = ($defaultFrequencyNumber) ? 'IFNULL(fr.frequency_number,:defaultNumber) as frequency_number' : 'fr.frequency_number';
        $selectNumber    = ($defaultFrequencyTime) ? 'IFNULL(fr.frequency_time,:frequencyTime) as frequency_time' : 'fr.frequency_time';

        $q->select("es.lead_id, $selectFrequency, $selectNumber")
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
            ->leftJoin('es', MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr', 'es.lead_id = fr.lead_id');

        if ($channel) {
            $q->andWhere('fr.channel = :channel or fr.channel is null')
                ->setParameter('channel', $channel);
        }

        if (!empty($defaultFrequencyTime)) {
            $q->andWhere('es.date_sent >= case fr.frequency_time 
                    when \'MONTH\' then DATE_SUB(NOW(),INTERVAL 1 MONTH) 
                    when \'DAY\' then DATE_SUB(NOW(),INTERVAL 1 DAY) 
                    when \'WEEK\' then DATE_SUB(NOW(),INTERVAL 1 WEEK)
                    else DATE_SUB(NOW(),INTERVAL 1 '.$defaultFrequencyTime.')
                    end')
                ->setParameter('frequencyTime', $defaultFrequencyTime);
        } else {
            $q->andWhere('(es.date_sent >= case fr.frequency_time
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
            $q->expr()->in('es.lead_id', $leadIds)
        );

        $q->groupBy('es.lead_id, fr.frequency_time, fr.frequency_number');

        if ($defaultFrequencyNumber != null) {
            $q->having('(count(es.lead_id) >= IFNULL(fr.frequency_number,:defaultNumber))')
                ->setParameter('defaultNumber', $defaultFrequencyNumber);
        } else {
            $q->having('(count(es.lead_id) >= fr.frequency_number)');
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param null $channel
     * @param null $leadId
     *
     * @return array
     */
    public function getFrequencyRules($channel = null, $leadId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('fr.id, fr.frequency_time, fr.frequency_number, fr.channel, fr.pause_from_date, fr.pause_to_date')
            ->from(MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr');

        if ($channel) {
            $q->andWhere('fr.channel = :channel')
                ->setParameter('channel', $channel);
        }

        if ($leadId) {
            $q->andWhere('fr.lead_id = :leadId')
                ->setParameter('leadId', $leadId);
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

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
