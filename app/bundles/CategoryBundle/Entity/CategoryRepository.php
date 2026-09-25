<?php

namespace Mautic\CategoryBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Category>
 */
final class CategoryRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @return Paginator
     */
    public function getEntities(array $args = []): iterable
    {
        $q = $this
            ->createQueryBuilder('c')
            ->select('c');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * $search is not typed because AssetModel::getLookupResults() calls this with
     * ($filter, $limit, 0), so it arrives as the limit - see the is_array() branch below.
     *
     * @param mixed $search
     *
     * @return mixed[]
     */
    public function getCategoryList(string $bundle, $search = '', int $limit = 10, int $start = 0, bool $includeGlobal = true): array
    {
        $q = $this->createQueryBuilder('c');
        $q->select('partial c.{id, title, alias, color, bundle}');

        $q->where('c.isPublished = :true')
            ->setParameter('true', true, 'boolean');

        $expr = $q->expr()->orX(
            $q->expr()->eq('c.bundle', ':bundle')
        );

        if ($includeGlobal && 'global' !== $bundle) {
            $expr->add(
                $q->expr()->eq('c.bundle', $q->expr()->literal('global'))
            );
        }

        $q->andWhere($expr)
          ->setParameter('bundle', $bundle);

        if (!empty($search)) {
            if (is_array($search)) {
                $search = array_map(intval(...), $search);
                $q->andWhere($q->expr()->in('c.id', ':search'))
                    ->setParameter('search', $search);
            } else {
                $q->andWhere($q->expr()->like('c.title', ':search'))
                    ->setParameter('search', "{$search}%");
            }
        }

        $q->orderBy('c.title');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    protected function addCatchAllWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    {
        return $this->addStandardCatchAllWhereClause($queryBuilder, $filter, [
            'c.title',
            'c.description',
        ]);
    }

    protected function addSearchCommandWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    {
        $command                 = $field                 = $filter->command;
        $unique                  = $this->generateRandomParameterName();
        [$expr, $parameters]     = parent::addSearchCommandWhereClause($queryBuilder, $filter);

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
            case $this->translator->trans('mautic.core.searchcommand.ispublished', [], null, 'en_US'):
                $expr                = $queryBuilder->expr()->eq('c.isPublished', ":{$unique}");
                $parameters[$unique] = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
            case $this->translator->trans('mautic.core.searchcommand.isunpublished', [], null, 'en_US'):
                $expr                = $queryBuilder->expr()->eq('c.isPublished', ":{$unique}");
                $parameters[$unique] = false;
                break;
        }

        if ($expr && $filter->not) {
            $expr = $queryBuilder->expr()->not($expr);
        }

        return [
            $expr,
            $parameters,
        ];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        $commands = [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['c.title', 'ASC'],
        ];
    }

    /**
     * @param string $bundle
     * @param string $alias
     * @param object $entity
     */
    public function checkUniqueCategoryAlias($bundle, $alias, $entity = null): int
    {
        $q = $this->createQueryBuilder('e')
            ->select('count(e.id) as aliascount')
            ->where('e.alias = :alias')
            ->andWhere('e.bundle = :bundle')
            ->setParameter('alias', $alias)
            ->setParameter('bundle', $bundle);

        if (!empty($entity) && $entity->getId()) {
            $q->andWhere('e.id != :id');
            $q->setParameter('id', $entity->getId());
        }

        $results = $q->getQuery()->getSingleResult();

        return $results['aliascount'];
    }

    public function getTableAlias(): string
    {
        return 'c';
    }
}
