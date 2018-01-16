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

class ForeignValueFilterQueryBuilder extends BaseFilterQueryBuilder
{
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.foreign.value';
    }

    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();

        $filterParameters = $filter->getParameterValue();

        if (is_array($filterParameters)) {
            $parameters = [];
            foreach ($filterParameters as $filterParameter) {
                $parameters[] = $this->generateRandomParameterName();
            }
        } else {
            $parameters = $this->generateRandomParameterName();
        }

        $filterParametersHolder = $filter->getParameterHolder($parameters);

        $tableAlias = $queryBuilder->getTableAlias($filter->getTable());

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();

            $queryBuilder = $queryBuilder->leftJoin(
                $queryBuilder->getTableAlias('leads'),
                $filter->getTable(),
                $tableAlias,
                $tableAlias.'.lead_id = l.id'
            );
        }

        switch ($filterOperator) {
            case 'empty':
                $queryBuilder->addSelect($tableAlias.'.lead_id');
                $expression = $queryBuilder->expr()->isNull(
                    $tableAlias.'.lead_id');
                $queryBuilder->andWhere($expression);
                break;
            case 'notEmpty':
                $queryBuilder->addSelect($tableAlias.'.lead_id');
                $expression = $queryBuilder->expr()->isNull(
                    $tableAlias.'.lead_id');
                $queryBuilder->andWhere($expression);
                break;
            default:
                $expression = $queryBuilder->expr()->$filterOperator(
                    $tableAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                $queryBuilder->addJoinCondition($tableAlias, ' ('.$expression.')');
                $queryBuilder->setParametersPairs($parameters, $filterParameters);
        }

        return $queryBuilder;
    }
}
