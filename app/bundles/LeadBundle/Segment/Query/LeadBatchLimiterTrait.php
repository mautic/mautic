<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query;

/**
 * Heads-up! Do not use any query parameters within this trait as it could cause conflicts. This trait is used by many query builders.
 */
trait LeadBatchLimiterTrait
{
    use BaseBatchLimiterTrait;

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addMinMaxLimiters(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'lead_id'): void
    {
        $this->addMinMaxLimitersGeneric($queryBuilder, $batchLimiters, $tableName, $columnName);
    }

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addLeadLimiter(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'lead_id'): void
    {
        $this->addEntityIdLimiterGeneric($queryBuilder, $batchLimiters, $tableName, $columnName, 'lead_id');
    }

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addLeadListLimiter(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'lead_id'): void
    {
        $this->addEntityListLimiterGeneric($queryBuilder, $batchLimiters, $tableName, $columnName);
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
