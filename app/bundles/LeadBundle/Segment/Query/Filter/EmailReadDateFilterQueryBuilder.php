<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

final readonly class EmailReadDateFilterQueryBuilder implements FilterQueryBuilderInterface
{
    public function __construct(
        private ForeignValueFilterQueryBuilder $foreignValueBuilder,
        private ForeignFuncFilterQueryBuilder $foreignFuncBuilder,
    ) {
    }

    public static function getServiceId(): string
    {
        return self::class;
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        // For lower date bounds, any qualifying read implies that the latest read qualifies.
        // The IN subquery can use the existing (date_read, lead_id) index.
        return match ($filter->getOperator()) {
            OperatorOptions::GREATER_THAN, OperatorOptions::GREATER_THAN_OR_EQUAL => $this->foreignValueBuilder->applyQuery($queryBuilder, $filter),
            default => $this->foreignFuncBuilder->applyQuery($queryBuilder, $filter),
        };
    }
}
