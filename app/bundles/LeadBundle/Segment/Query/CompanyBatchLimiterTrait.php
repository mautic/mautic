<?php

namespace Mautic\LeadBundle\Segment\Query;

/**
 * Company-specific batch limiting with 'company_id' as default.
 * Uses BaseBatchLimiterTrait for generic logic.
 *
 * Companies only need min/max limiting for batch processing.
 * No single entity limiting or list limiting is needed in the company segment flow.
 *
 * Heads-up! Do not use any query parameters within this trait as it could cause conflicts.
 */
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
