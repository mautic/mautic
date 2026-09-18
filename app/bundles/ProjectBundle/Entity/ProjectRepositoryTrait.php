<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

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

        $connection = $queryBuilder->getConnection(); /** @phpstan-ignore-line getConnection is deprecated */
        $platform   = $connection->getDatabasePlatform();

        $queryBuilder->where(
            DatabasePlatform::getCaseInsensitiveLike($platform, 'project.name', ':name')
        );

        $queryBuilder->setParameter('name', $projectName);
        $ids = $queryBuilder->executeQuery()->fetchFirstColumn() ?: [0];
        $ids = array_map(fn ($id): string => (string) intval($id), $ids);

        if ($negation) {
            $expr = $queryBuilder->expr()->notIn("{$parentTableAlias}.id", $ids);
        } else {
            $expr = $queryBuilder->expr()->in("{$parentTableAlias}.id", $ids);
        }

        return [$expr, []];
    }
}
