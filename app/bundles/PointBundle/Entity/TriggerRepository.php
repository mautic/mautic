<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\Common\Collections\Order;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Trigger>
 */
class TriggerRepository extends CommonRepository
{
    public function getEntities(array $args = [])
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias().', cat')
            ->from(Trigger::class, $this->getTableAlias())
            ->leftJoin($this->getTableAlias().'.category', 'cat')
            ->leftJoin($this->getTableAlias().'.group', 'pl');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * Get a list of published triggers with color and points.
     *
     * @return array
     */
    public function getTriggerColors()
    {
        $q = $this->_em->createQueryBuilder()
            ->select('partial t.{id, color, points}')
            ->from(Trigger::class, 't', 't.id');

        $q->where($this->getPublishedByDateExpression($q));
        $q->orderBy('t.points', Order::Ascending->value);

        return $q->getQuery()->getArrayResult();
    }

    public function getTableAlias(): string
    {
        return 't';
    }

    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            't.name',
            't.description',
        ]);
    }

    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }
}
