<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\PointBundle\Entity\Group;

/**
 * @extends CommonRepository<PointsChangeLog>
 */
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
        $connection = $this->getEntityManager()->getConnection();

        $query = $connection->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'lead_points_change_log', 'lp')
            ->select('lp.event_name as '.$connection->quoteIdentifier('eventName'),
                'lp.action_name as '.$connection->quoteIdentifier('actionName'),
                'lp.date_added as '.$connection->quoteIdentifier('dateAdded'),
                'lp.type',
                'lp.delta',
                'lp.id',
                'lp.lead_id',
                'pl.name as '.$connection->quoteIdentifier('groupName'))
            ->leftJoin('lp', MAUTIC_TABLE_PREFIX.Group::TABLE_NAME, 'pl', 'lp.group_id = pl.id');

        if ($leadId) {
            $query->where('lp.lead_id = '.(int) $leadId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->or(
                    $query->expr()->like('lp.event_name', ':search'),
                    $query->expr()->like('lp.action_name', ':search')
                )
            )->setParameter('search', '%'.$options['search'].'%');
        }

        return $this->getTimelineResults($query, $options, 'lp.event_name', 'lp.date_added', [], ['dateAdded'], null, 'lp.id');
    }

    /**
     * Get table stat data from point log table.
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostPoints(QueryBuilder $query, $limit = 10, $offset = 0): array
    {
        $query->setMaxResults($limit)
                ->setFirstResult($offset);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get table stat data from lead table.
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostLeads(QueryBuilder $query, $limit = 10, $offset = 0): array
    {
        $query->setMaxResults($limit)
                ->setFirstResult($offset);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param int $fromLeadId
     * @param int $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'lead_points_change_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->executeStatement();
    }

    public function getTableAlias(): string
    {
        return 'lp';
    }
}
