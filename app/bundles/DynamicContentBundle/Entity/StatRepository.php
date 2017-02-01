<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * Class StatRepository.
 */
class StatRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * @param   $dynamicContentId
     *
     * @return array
     */
    public function getSentStats($dynamicContentId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', 's')
            ->where('s.dynamic_content_id = :dynamic_content')
            ->setParameter('dynamic_content', $dynamicContentId);

        $result = $q->execute()->fetchAll();

        // index by lead
        $stats = [];

        foreach ($result as $r) {
            $stats[$r['lead_id']] = $r['lead_id'];
        }

        unset($result);

        return $stats;
    }

    /**
     * @param int|array $dynamicContentIds
     *
     * @return int
     */
    public function getSentCount($dynamicContentIds = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', 's');

        if ($dynamicContentIds) {
            if (!is_array($dynamicContentIds)) {
                $dynamicContentIds = [(int) $dynamicContentIds];
            }
            $q->where(
                $q->expr()->in('s.dynamic_content_id', $dynamicContentIds)
            );
        }

        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['sent_count'] : 0;
    }

    /**
     * Get sent counts based grouped by dynamic content Id.
     *
     * @param array     $dynamicContentIds
     * @param \DateTime $fromDate
     *
     * @return array
     */
    public function getSentCounts($dynamicContentIds = [], \DateTime $fromDate = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.dynamic_content_id, count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', 's')
            ->andWhere(
                $q->expr()->in('e.dynamic_content_id', $dynamicContentIds)
            );

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $q->andWhere(
                $q->expr()->gte('e.date_sent', $q->expr()->literal($dt->toUtcString()))
            );
        }
        $q->groupBy('e.dynamic_content_id');

        //get a total number of sent DC stats first
        $results = $q->execute()->fetchAll();

        $counts = [];

        foreach ($results as $r) {
            $counts[$r['dynamic_content_id']] = $r['sent_count'];
        }

        return $counts;
    }

    /**
     * Get a lead's dynamic content stat.
     *
     * @param int   $leadId
     * @param array $options
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($leadId, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $query->select('dc.id AS dynamic_content_id, s.id, s.date_sent as dateSent, dc.name, s.sent_details as sentDetails')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', 's')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'dynamic_content', 'dc', 'dc.id = s.dynamic_content_id')
            ->where($query->expr()->eq('s.lead_id', (int) $leadId));

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->like('dc.name', $query->expr()->literal('%'.$options['search'].'%'))
            );
        }

        return $this->getTimelineResults($query, $options, 'dc.name', 's.date_sent', ['sentDetails'], ['dateSent']);
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'dynamic_content_stats')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->execute();
    }

    /**
     * Delete a stat.
     *
     * @param $id
     */
    public function deleteStat($id)
    {
        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', ['id' => (int) $id]);
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 's';
    }
}
