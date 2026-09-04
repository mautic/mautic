<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\Common\Collections\Order;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ProjectBundle\Entity\ProjectRepositoryTrait;

/**
 * @extends CommonRepository<Trigger>
 */
class TriggerRepository extends CommonRepository
{
    use ProjectRepositoryTrait;

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

        $q->where($this->getPublishedByDateOrmExpression($q));
        $q->orderBy('t.points', Order::Ascending->value);

        return $q->getQuery()->getArrayResult();
    }

    public function getTableAlias(): string
    {
        return 't';
    }

    protected function addCatchAllWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    {
        return $this->addStandardCatchAllWhereClause($queryBuilder, $filter, [
            't.name',
            't.description',
        ]);
    }

    protected function addSearchCommandWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    {
        return match ($filter->command) {
            $this->translator->trans('mautic.project.searchcommand.name'), $this->translator->trans('mautic.project.searchcommand.name', [], null, 'en_US') => $this->handleProjectFilter(
                $this->_em->getConnection()->createQueryBuilder(),
                'point_trigger_id',
                'point_trigger_projects_xref',
                $this->getTableAlias(),
                $filter->string,
                $filter->not
            ),
            // Handle standard search commands
            default => $this->addStandardSearchCommandWhereClause($queryBuilder, $filter),
        };
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge(['mautic.project.searchcommand.name'], $this->getStandardSearchCommands());
    }
}
