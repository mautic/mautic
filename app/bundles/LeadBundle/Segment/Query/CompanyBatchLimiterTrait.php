<?php

namespace Mautic\LeadBundle\Segment\Query;

trait CompanyBatchLimiterTrait
{
    use BaseBatchLimiterTrait;

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function addMinMaxLimiters(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName = 'company_id'): void
    {
        $this->addMinMaxLimitersGeneric($queryBuilder, $batchLimiters, $tableName, $columnName);
    }
}
