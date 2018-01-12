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

class ForeignFilterQueryBuilder implements FilterQueryBuilderInterface
{
    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        // TODO: Implement applyQuery() method.

        dump('Aplying any query for me: '.$filter->__toString());

        $glueFunc = $filter->get    Glue().'Where';

        $parameterName = $this->generateRandomParameterName();

        $queryBuilder = $this->createExpression($queryBuilder, $parameterName, $this->getFunc());

        $queryBuilder->setParameter($parameterName, $this->getFilter());

        dump($queryBuilder->getSQL());


        return $queryBuilder;
    }
}
