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
    public function getAppliedFrequencyRules($channel = null, $ids = null, $listId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('fr.frequency_time, fr.frequency_number')
            ->from(MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'fr')
            ->join('fr', MAUTIC_TABLE_PREFIX.'email_stats', 'es','es.lead_id = fr.lead_id and es.date_sent <= (es.date_sent INTERVAL fr.frequency_time' );


        if ($channel) {
            $q->andWhere('fr.channel = :channel')
                ->setParameter('channel', $channel);
        }

        if ($listId) {
            $q->leftJoin('fr', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'cs', 'cs.lead_id = fr.lead_id')
                ->andWhere('cs.leadlist_id = :list_id')
                ->setParameter('list_id', $listId);
        }
        $q->having('count(es.email_address) > fr.frequency_number');

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
