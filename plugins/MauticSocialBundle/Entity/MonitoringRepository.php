<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Monitoring>
 */
final class MonitoringRepository extends CommonRepository
{
    /**
     * @return Paginator
     */
    public function getPublishedEntities(array $args = [])
    {
        $q    = $this->createQueryBuilder($this->getTableAlias());
        $expr = $this->getPublishedByDateExpression($q);

        $q->where($expr);
        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    public function getPublishedEntitiesCount(): int
    {
        $q    = $this->createQueryBuilder($this->getTableAlias());
        $expr = $this->getPublishedByDateExpression($q);
        $q->where($expr);
        $args['qb'] = $q;

        return count(parent::getEntities($args));
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
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
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    public function getTableAlias(): string
    {
        return 'e';
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }
}
