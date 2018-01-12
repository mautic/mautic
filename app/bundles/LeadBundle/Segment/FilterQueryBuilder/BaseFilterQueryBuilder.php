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
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;

class BaseFilterQueryBuilder implements FilterQueryBuilderInterface
{
    use LeadSegmentFilterQueryBuilderTrait;

    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();
        $filterGlue     = $filter->getGlue();
        $filterAggr     = $filter->getAggregateFunction();

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

        dump(sprintf('START filter query for %s, operator: %s, %s', $filter->__toString(), $filter->getOperator(), print_r($parameters, true)));

        $filterGlueFunc = $filterGlue.'Where';

        $tableAlias = $this->getTableAlias($filter->getTable(), $queryBuilder);

        // for aggregate function we need to create new alias and not reuse the old one
        if ($filterAggr) {
            $tableAlias = false;
        }

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();

            switch ($filterOperator) {
                case 'notLike':
                case 'notIn':

                case 'empty':
                case 'startsWith':
                case 'gt':
                case 'eq':
                case 'neq':
                case 'gte':
                case 'like':
                case 'lt':
                case 'lte':
                case 'in':
                    if ($filterAggr) {
                        $queryBuilder = $queryBuilder->leftJoin(
                            $this->getTableAlias('leads', $queryBuilder),
                            $filter->getTable(),
                            $tableAlias,
                            sprintf('%s.id = %s.lead_id', $this->getTableAlias('leads', $queryBuilder), $tableAlias)
                        );
                    } else {
                        $queryBuilder = $queryBuilder->innerJoin(
                            $this->getTableAlias('leads', $queryBuilder),
                            $filter->getTable(),
                            $tableAlias,
                            sprintf('%s.id = %s.lead_id', $this->getTableAlias('leads', $queryBuilder), $tableAlias)
                        );
                    }
                    break;
                default:
                    throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
            }

            var_dump('This QB is not intended for foreign queries, add entity "'.$filter->getTable().'"" first.');
        }

        switch ($filterOperator) {
            case 'empty':
                $expression = $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()),
                    $queryBuilder->expr()->eq($tableAlias.'.'.$filter->getField(), ':'.$emptyParameter = $this->generateRandomParameterName())
                );
                $queryBuilder->setParameter($emptyParameter, '');
                break;
            case 'gt':
            case 'eq':
            case 'neq':
            case 'gte':
            case 'like':
            case 'notLike':
            case 'lt':
            case 'lte':
            case 'notIn':
            case 'in':
                if ($filterAggr) {
                    $expression = $queryBuilder->expr()->$filterOperator(
                        sprintf('%s(%s)', $filterAggr, $tableAlias.'.'.$filter->getField()),
                        $filterParametersHolder
                    );
                } else {
                    $expression = $queryBuilder->expr()->$filterOperator(
                        $tableAlias.'.'.$filter->getField(),
                        $filterParametersHolder
                    );
                }
                break;
            default:
                var_dump($filter->toArray());
                throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
        }

        if ($this->isJoinTable($filter->getTable(), $queryBuilder)) {
            if ($filterAggr) {
                $queryBuilder->andHaving($expression);
            } else {
                $queryBuilder->addJoinCondition($tableAlias, $expression);
            }
        } else {
            $queryBuilder->$filterGlueFunc($expression);
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        dump('DONE aplying query for me: '.$filter->__toString());
        dump($queryBuilder->getQueryParts());
        dump($queryBuilder->getParameters());

        return $queryBuilder;
    }
}
