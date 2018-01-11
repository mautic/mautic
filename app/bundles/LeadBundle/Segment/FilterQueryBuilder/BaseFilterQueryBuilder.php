<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 1/11/18
 * Time: 11:24 AM.
 */

namespace Mautic\LeadBundle\Segment\FilterQueryBuilder;

use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

class BaseFilterQueryBuilder implements FilterQueryBuilderInterface
{
    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        // TODO: Implement applyQuery() method.

        dump('Not aplying any query for me: '.$filter->__toString());

        return $queryBuilder;
    }
}
