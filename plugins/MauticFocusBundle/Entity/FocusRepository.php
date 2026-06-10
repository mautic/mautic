<?php

namespace MauticPlugin\MauticFocusBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ProjectBundle\Entity\ProjectRepositoryTrait;

/**
 * @extends CommonRepository<Focus>
 */
class FocusRepository extends CommonRepository
{
    use ProjectRepositoryTrait;

    /**
     * @return array
     */
    public function findByForm($formId)
    {
        return $this->findBy(
            [
                'form' => (int) $formId,
            ]
        );
    }

    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        $q = $this->_em
            ->createQueryBuilder()
            ->select($alias)
            ->from(Focus::class, $alias, $alias.'.id');

        if (empty($args['iterable_mode'])) {
            $q->leftJoin($alias.'.category', 'c');
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['f.name', 'f.website']);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return match ($filter->command) {
            $this->translator->trans('mautic.project.searchcommand.name'),
            $this->translator->trans('mautic.project.searchcommand.name', [], null, 'en_US') => $this->handleProjectFilter(
                $this->_em->getConnection()->createQueryBuilder(),
                'focus_id',
                'focus_projects_xref',
                $this->getTableAlias(),
                $filter->string,
                $filter->not
            ),
            default => $this->addStandardSearchCommandWhereClause($q, $filter),
        };
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge([
            'mautic.project.searchcommand.name',
        ], $this->getStandardSearchCommands());
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.name', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'f';
    }

    /**
     * @return array
     */
    public function getFocusList($currentId)
    {
        $q = $this->createQueryBuilder('f');
        $q->select('partial f.{id, name, description}')->orderBy('f.name');

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Published, filter-based focus items. Only id + filters are fetched as the
     * focus table carries heavy LONGTEXT columns and this runs on every page view.
     *
     * @return array<int, array{id: string, filters: string}>
     */
    public function getPublishedWithFilters(): array
    {
        return $this->getPublishedWithFiltersQuery()
            ->select('f.id, f.filters')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function hasPublishedWithFilters(): bool
    {
        return (bool) $this->getPublishedWithFiltersQuery()
            ->select('f.id')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    private function getPublishedWithFiltersQuery(): \Doctrine\DBAL\Query\QueryBuilder
    {
        $qb = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'focus', 'f');

        // 'N;' and 'a:0:{}' are how the array column type serializes null/empty filters
        return $qb
            ->where($this->getPublishedByDateExpression($qb, 'f'))
            ->andWhere('f.filters IS NOT NULL')
            ->andWhere("f.filters NOT IN ('a:0:{}', 'N;')");
    }
}
