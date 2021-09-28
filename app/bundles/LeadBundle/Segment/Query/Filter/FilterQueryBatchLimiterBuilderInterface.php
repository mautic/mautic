<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

interface FilterQueryBatchLimiterBuilderInterface
{
    public function applyFilterOperators(QueryBuilder $queryBuilder, ContactSegmentFilter $filter, QueryBuilder $filterQueryBuilder): QueryBuilder;

    public function addMinMaxLimiters(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName): void;

    public function addLeadLimiter(QueryBuilder $queryBuilder, array $batchLimiters, string $tableName, string $columnName): void;
}
