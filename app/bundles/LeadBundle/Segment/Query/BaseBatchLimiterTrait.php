<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query;

/**
 * Provides generic methods that can be used for any entity type (leads, companies, etc.).
 * Heads-up! Do not use any query parameters within this trait as it could cause conflicts.
 */
trait BaseBatchLimiterTrait
{
    /**
     * @param array<string, mixed> $batchLimiters
     */
    protected function addMinMaxLimitersGeneric(
        QueryBuilder $queryBuilder,
        array $batchLimiters,
        string $tableName,
        string $columnName,
    ): void {
        $tableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.$tableName);

        if (!empty($batchLimiters['minId']) && !empty($batchLimiters['maxId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->comparison(
                    $tableAlias.'.'.$columnName,
                    'BETWEEN',
                    "{$batchLimiters['minId']} and {$batchLimiters['maxId']}"
                )
            );
        } elseif (!empty($batchLimiters['maxId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lte($tableAlias.'.'.$columnName, (int) $batchLimiters['maxId'])
            );
        } elseif (!empty($batchLimiters['minId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gte($tableAlias.'.'.$columnName, (int) $batchLimiters['minId'])
            );
        }
    }

    /**
     * Generic single entity limiter.
     * Adds WHERE condition to limit to a specific entity ID.
     *
     * @param array<string, mixed> $batchLimiters
     */
    protected function addEntityIdLimiterGeneric(
        QueryBuilder $queryBuilder,
        array $batchLimiters,
        string $tableName,
        string $columnName,
        string $limitKey,
    ): void {
        if (empty($batchLimiters[$limitKey])) {
            return;
        }

        $tableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.$tableName);
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq($tableAlias.'.'.$columnName, (int) $batchLimiters[$limitKey])
        );
    }

    /**
     * Generic entity list limiter.
     * Adds WHERE IN condition to limit to a list of entity IDs.
     *
     * @param array<string, mixed> $batchLimiters
     */
    protected function addEntityListLimiterGeneric(
        QueryBuilder $queryBuilder,
        array $batchLimiters,
        string $tableName,
        string $columnName,
    ): void {
        if (empty($batchLimiters['ids'])) {
            return;
        }

        $ids = array_unique(array_filter(array_map(
            fn ($id): string => (string) (int) $id,
            (array) $batchLimiters['ids']
        )));

        if (!$ids) {
            return;
        }

        $tableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.$tableName);
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in($tableAlias.'.'.$columnName, $ids)
        );
    }
}
