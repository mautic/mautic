<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

interface FilterQueryBuilderInterface
{
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder;

    /**
     * @return string returns the service id in the DIC container
     */
    public static function getServiceId(): string;
}
