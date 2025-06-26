<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;

trait ProjectRepositoryTrait
{
    /**
     * @return array{0: string, 1: array<string, array<int|string>>}
     */
    private function handleProjectFilter(QueryBuilder $queryBuilder, string $idColumn, string $xrefTable, string $parentTableAlias, string $projectName, bool $negation): array
    {
        $queryBuilder->select($idColumn);
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.$xrefTable, 'projectxref');
        $queryBuilder->innerJoin(
            'projectxref',
            MAUTIC_TABLE_PREFIX.'projects',
            'project',
            'project.id = projectxref.project_id'
        );
        $queryBuilder->where($queryBuilder->expr()->eq('project.name', ':name'));
        $queryBuilder->setParameter('name', $projectName);
        $ids = $queryBuilder->executeQuery()->fetchFirstColumn() ?: [0];
        $ids = array_map(fn ($value) => "'$value'", $ids);

        if ($negation) {
            $expr = $queryBuilder->expr()->notIn("{$parentTableAlias}.id", $ids);
        } else {
            $expr = $queryBuilder->expr()->in("{$parentTableAlias}.id", $ids);
        }

        return [$expr, []];
    }
}
