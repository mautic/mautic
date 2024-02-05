<?php

namespace MauticPlugin\MauticFocusBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * @extends CommonRepository<Stat>
 */
class StatRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Fetch the base stat data from the database.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function getStats($id, $type, $fromDate = null)
    {
        $q = $this->createQueryBuilder('s');

        $expr = $q->expr()->andX(
            $q->expr()->eq('IDENTITY(s.focus)', (int) $id),
            $q->expr()->eq('s.type', ':type')
        );

        if ($fromDate) {
            $expr->add(
                $q->expr()->gte('s.dateAdded', ':fromDate')
            );
            $q->setParameter('fromDate', $fromDate);
        }

        $q->where($expr)
            ->setParameter('type', $type);

        return $q->getQuery()->getArrayResult();
    }

    public function getViewsCount(int $id): int
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('COUNT(s.id) as views_count')
            ->from(MAUTIC_TABLE_PREFIX.'focus_stats', 's');

        $expr = $q->expr()->and(
            $q->expr()->eq('s.focus_id', ':id'),
            $q->expr()->eq('s.type', ':type')
        );

        $q->where($expr)
            ->setParameter('id', $id)
            ->setParameter('type', Stat::TYPE_NOTIFICATION);

        return (int) $q->executeQuery()->fetchOne();
    }

    public function getUniqueViewsCount(int $id): int
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('COUNT(DISTINCT s.lead_id) as views_count')
            ->from(MAUTIC_TABLE_PREFIX.'focus_stats', 's');

        $expr = $q->expr()->and(
            $q->expr()->eq('s.focus_id', ':id'),
            $q->expr()->eq('s.type', ':type')
        );

        $q->where($expr)
            ->setParameter('id', $id)
            ->setParameter('type', Stat::TYPE_NOTIFICATION);

        return (int) $q->executeQuery()->fetchOne();
    }

    public function getClickThroughCount(int $id): int
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('COUNT(DISTINCT s.lead_id) as click_through_count')
            ->from(MAUTIC_TABLE_PREFIX.'focus_stats', 's');

        $expr = $q->expr()->and(
            $q->expr()->eq('s.focus_id', ':id'),
            $q->expr()->eq('s.type', ':type')
        );

        $q->where($expr)
            ->setParameter('id', $id)
            ->setParameter('type', Stat::TYPE_CLICK);

        return (int) $q->executeQuery()->fetchOne();
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function getStatsViewByLead(?int $leadId=null, array $options = []): array
    {
        return $this->getStatsByLeadAndType(Stat::TYPE_NOTIFICATION, $leadId, $options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function getStatsClickByLead(?int $leadId=null, array $options = []): array
    {
        return $this->getStatsByLeadAndType(Stat::TYPE_CLICK, $leadId, $options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function getStatsByLeadAndType(string $type, ?int $leadId=null, array $options = []): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->from(MAUTIC_TABLE_PREFIX.'focus_stats', 's')
            ->select('s.id, s.lead_id, s.type, s.date_added, f.id as focus_id, f.name as focus_name')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'focus', 'f', 's.focus_id=f.id');

        $q->where($q->expr()->eq('s.type', ':type'));

        if ($leadId) {
            $q->andWhere($q->expr()->eq('s.lead_id', (int) $leadId));
        }

        $q->setParameter('type', $type);

        if (isset($options['search']) && $options['search']) {
            $q->andWhere($q->expr()->or(
                $q->expr()->like('f.name', $q->expr()->literal($options['search'].'%')),
                $q->expr()->eq('s.type', $q->expr()->literal($options['search']))
            ));
        }

        return $this->getTimelineResults($q, $options, 'f.name', 's.date_added');
    }
}
