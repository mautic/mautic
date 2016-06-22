<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Doctrine\ORM\Query;

/**
 * PointsChangeLogRepository
 */
class PointsChangeLogRepository extends CommonRepository
{
    /**
     * Get a lead's point log
     *
     * @param integer $leadId
     * @param array   $options
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadTimelineEvents($leadId, array $options = array())
    {
        $query = $this->createQueryBuilder('lp')
            ->select('lp.eventName, lp.actionName, lp.dateAdded, lp.type, lp.delta')
            ->where('lp.lead = ' . $leadId);

        if (!empty($options['ipIds'])) {
            $query->orWhere('lp.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['filters']['search']) && $options['filters']['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('lp.eventName', $query->expr()->literal('%' . $options['filters']['search'] . '%')),
                $query->expr()->like('lp.actionName', $query->expr()->literal('%' . $options['filters']['search'] . '%'))
            ));
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get table stat data from point log table
     *
     * @param QueryBuilder $query
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostPoints($query, $limit = 10, $offset = 0)
    {
        $query->setMaxResults($limit)
                ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();
        return $results;
    }

    /**
     * Get table stat data from lead table
     *
     * @param QueryBuilder $query
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostLeads($query, $limit = 10, $offset = 0)
    {
        $query->setMaxResults($limit)
                ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();
        return $results;
    }

    /**
     * Count a value in a column
     *
     * @param QueryBuilder $query
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countValue($query, $column, $value)
    {
        $query->select('count(' . $column . ') as quantity')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_points_change_log', 'lp', 'lp.lead_id = l.id')
            ->andwhere($query->expr()->eq($column, ':value'))
            ->setParameter('value', $value);

        $result = $query->execute()->fetch();

        return $result['quantity'];
    }

    /**
     * Updates lead ID (e.g. after a lead merge)
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'lead_points_change_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = ' . (int) $fromLeadId)
            ->execute();
    }
}
