<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Monitoring>
 */
class MonitoringRepository extends CommonRepository
{
    /**
     * @param array $args
     *
     * @return Paginator
     */
    public function getPublishedEntities($args = [])
    {
        $q    = $this->createQueryBuilder($this->getTableAlias());
        $expr = $this->getPublishedByDateExpression($q);

        $q->where($expr);
        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @return float|int
     */
    public function getPublishedEntitiesCount()
    {
        $q    = $this->createQueryBuilder($this->getTableAlias());
        $expr = $this->getPublishedByDateExpression($q);
        $q->where($expr);
        $args['qb'] = $q;

        return parent::getEntities($args)->count();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [
                $this->getTableAlias().'.title',
                $this->getTableAlias().'.description',
            ]
        );
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias(): string
    {
        return 'e';
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }
}
