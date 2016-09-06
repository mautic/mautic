<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * FrequecyRuleRepository
 */
class FrequencyRuleRepository extends CommonRepository
{
    /**
     * @param string    $channel
     * @param array|int $ids
     * @param int       $listId
     *
     * @return array
     */
    public function getAppliedFrequencyRules($channel = null, $leadIds = null, $listId, $defaultFrequencyNumber, $defaultFrequencyTime)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('es.lead_id, fr.frequency_time, fr.frequency_number')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
            ->join('es', MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr','es.lead_id = fr.lead_id' );

        if ($channel) {
            $q->andWhere('fr.channel = :channel or fr.channel is null')
                ->setParameter('channel', $channel);
        }

        if ($listId) {
            $q->leftJoin('fr', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'cs', 'cs.lead_id = fr.lead_id')
                ->andWhere('cs.leadlist_id = :list_id')
                ->setParameter('list_id', $listId);
        }
        if (!empty($defaultFrequencyTime)) {
            $q->andWhere('es.date_sent >= case fr.frequency_time 
                    when \'MONTH\' then DATE_SUB(NOW(),INTERVAL 1 MONTH) 
                    when \'DAY\' then DATE_SUB(NOW(),INTERVAL 1 DAY) 
                    when \'WEEK\' then DATE_SUB(NOW(),INTERVAL 1 WEEK)
                    else DATE_SUB(NOW(),INTERVAL 1 '.$defaultFrequencyTime.')
                    end');
        } else {
            $q->andWhere('(case fr.frequency_time 
                     when \'MONTH\' then DATE_SUB(NOW(),INTERVAL 1 MONTH) 
                     when \'DAY\' then DATE_SUB(NOW(),INTERVAL 1 DAY) 
                     when \'WEEK\' then DATE_SUB(NOW(),INTERVAL 1 WEEK) 
                    end)');
        }

        if ($leadIds) {
            $q->andWhere('es.lead_id in (:lead_ids)')
                ->setParameter('lead_ids', $leadIds);
        }

        $q->groupBy('es.lead_id, fr.frequency_time, fr.frequency_number');

        if ($defaultFrequencyNumber != null) {
            $q->having('(count(es.lead_id) > fr.frequency_number and fr.frequency_number is not null) or (count(es.lead_id) < :defaultNumber)')
                ->setParameter('defaultNumber', $defaultFrequencyNumber);
        } else {
            $q->having('(count(es.lead_id) > fr.frequency_number)');
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param string    $channel
     * @param array|int $ids
     * @param int       $listId
     *
     * @return array
     */
    public function getFrequencyRules($channel = null, $leadId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('fr.id, fr.frequency_time, fr.frequency_number, fr.channel')
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
}
