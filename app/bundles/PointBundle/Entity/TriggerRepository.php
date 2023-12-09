<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Trigger>
 */
class TriggerRepository extends CommonRepository
{
    /**
     * {@inheritdoc}
     */
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

        $q->orderBy('t.points', Criteria::ASC);

        return $q->getQuery()->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias(): string
    {
        return 't';
    }

    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            't.name',
            't.description',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }
}
