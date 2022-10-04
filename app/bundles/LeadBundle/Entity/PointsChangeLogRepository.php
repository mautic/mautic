<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

class PointsChangeLogRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Get a lead's point log.
     *
     * @param int|null $leadId
     *
     * @return array
     */
    public function getLeadTimelineEvents($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'lead_points_change_log', 'lp')
            ->select('lp.event_name as eventName, lp.action_name as actionName, lp.date_added as dateAdded, lp.type, lp.delta, lp.id, lp.lead_id');

        if ($leadId) {
            $query->where('lp.lead_id = '.(int) $leadId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('lp.event_name', $query->expr()->literal('%'.$options['search'].'%')),
                $query->expr()->like('lp.action_name', $query->expr()->literal('%'.$options['search'].'%'))
            ));
        }

        return $this->getTimelineResults($query, $options, 'lp.event_name', 'lp.date_added', [], ['dateAdded']);
    }

    /**
     * Get table stat data from point log table.
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostPoints(QueryBuilder $query, $limit = 10, $offset = 0)
    {
        $query->setMaxResults($limit)
                ->setFirstResult($offset);

        return $query->execute()->fetchAll();
    }

    /**
     * Get table stat data from lead table.
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostLeads(QueryBuilder $query, $limit = 10, $offset = 0)
    {
        $query->setMaxResults($limit)
                ->setFirstResult($offset);

        return $query->execute()->fetchAll();
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param int $fromLeadId
     * @param int $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'lead_points_change_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->execute();
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'lp';
    }
}
