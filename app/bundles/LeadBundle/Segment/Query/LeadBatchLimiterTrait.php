<?php

namespace Mautic\LeadBundle\Segment\Query;

/**
 * Heads-up! Do not use any query parameters within this trait as it could cause conflicts. This trait is used by many query builders.
 */
trait LeadBatchLimiterTrait
{
    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addMinMaxLimiters(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'lead_id'): void
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.$tableName);

        if (!empty($batchLimiters['minId']) && !empty($batchLimiters['maxId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->comparison($leadsTableAlias.'.'.$columnName, 'BETWEEN', "{$batchLimiters['minId']} and {$batchLimiters['maxId']}")
            );
        } elseif (!empty($batchLimiters['maxId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lte($leadsTableAlias.'.'.$columnName, (int) $batchLimiters['maxId'])
            );
        } elseif (!empty($batchLimiters['minId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gte($leadsTableAlias.'.'.$columnName, (int) $batchLimiters['minId'])
            );
        }
    }

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addLeadLimiter(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'lead_id'): void
    {
        if (empty($batchLimiters['lead_id'])) {
            return;
        }

        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.$tableName);
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq($leadsTableAlias.'.'.$columnName, (int) $batchLimiters['lead_id'])
        );
    }

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addLeadListLimiter(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'lead_id'): void
    {
        if (empty($batchLimiters['ids'])) {
            return;
        }

        $ids = array_unique(array_filter(array_map(fn ($id) => (string) (int) $id, (array) $batchLimiters['ids'])));

        if (!$ids) {
            return;
        }

        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.$tableName);
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in($leadsTableAlias.'.'.$columnName, $ids)
        );
    }

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addLeadAndMinMaxLimiters(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'lead_id'): void
    {
        $this->addLeadLimiter($queryBuilder, $batchLimiters, $tableName, $columnName);
        $this->addLeadListLimiter($queryBuilder, $batchLimiters, $tableName, $columnName);
        $this->addMinMaxLimiters($queryBuilder, $batchLimiters, $tableName, $columnName);
    }
}
