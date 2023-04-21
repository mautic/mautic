<?php

namespace Mautic\LeadBundle\Segment\Query;

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
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.$tableName);

        if (empty($batchLimiters['lead_id'])) {
            return;
        }

        $queryBuilder->andWhere($leadsTableAlias.'.'.$columnName.' = '.$batchLimiters['lead_id']);
    }

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addLeadAndMinMaxLimiters(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'lead_id'): void
    {
        $this->addLeadLimiter($queryBuilder, $batchLimiters, $tableName, $columnName);
        $this->addMinMaxLimiters($queryBuilder, $batchLimiters, $tableName, $columnName);
    }
}
