<?php

namespace MauticPlugin\MauticFocusBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Stat>
 */
class StatRepository extends CommonRepository
{
    /**
     * Fetch the base stat data from the database.
     *
     * @param int  $id
     * @param      $type
     * @param null $fromDate
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

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function getStatsViewByLead(int $leadId, array $options = []): array
    {
        $q = $this->createQueryBuilder('s');
        $q
            ->select('partial s.{id, lead, type, dateAdded}, partial f.{id, name}')
            ->leftJoin('s.focus', 'f');

        $expr = $q->expr()->andX(
            $q->expr()->eq('IDENTITY(s.lead)', (int) $leadId),
            $q->expr()->in('s.type', ':type')
        );

        $q->where($expr)
            ->setParameter('type', [Stat::TYPE_NOTIFICATION, Stat::TYPE_CLICK]);

        if (isset($options['search']) && $options['search']) {
            $q->andWhere($q->expr()->orX(
                $q->expr()->like('f.name', $q->expr()->literal('%'.$options['search'].'%')),
                $q->expr()->like('f.description', $q->expr()->literal('%'.$options['search'].'%')),
                $q->expr()->like('s.type', $q->expr()->literal('%'.$options['search'].'%'))
            ));
        }

        $result = $q->getQuery()->getArrayResult();

        return ['result' => $result, 'total' => count($result)];
    }
}
