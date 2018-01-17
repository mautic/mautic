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

    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.basic';
    }

    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();
        $filterGlue     = $filter->getGlue();
        $filterAggr     = $filter->getAggregateFunction();

        // @debug we do not need this, it's just to verify we reference an existing database column
        try {
            $filter->getColumn();
        } catch (\Exception $e) {
            dump(' * ERROR * - Unhandled field '.sprintf(' %s, operator: %s, %s', $filter->__toString(), $filter->getOperator(), print_r($filterAggr, true)));

            return $queryBuilder;
        }

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

        $filterGlueFunc = $filterGlue.'Where';

        $tableAlias = $queryBuilder->getTableAlias($filter->getTable());

        // for aggregate function we need to create new alias and not reuse the old one
        if ($filterAggr) {
            $tableAlias = false;
        }

//        dump($filter->getTable()); if ($filter->getTable()=='companies') {
//            dump('companies');
//    }

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
                case 'regexp':
                case 'notRegexp':
                    //@todo this logic needs to
                    if ($filterAggr) {
                        $queryBuilder->leftJoin(
                            $queryBuilder->getTableAlias('leads'),
                            $filter->getTable(),
                            $tableAlias,
                            sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias('leads'), $tableAlias)
                        );
                    } else {
                        if ($filter->getTable() == 'companies') {
                            $relTable = $this->generateRandomParameterName();
                            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', $relTable, $relTable.'.lead_id = l.id');
                            $queryBuilder->leftJoin($relTable, $filter->getTable(), $tableAlias, $tableAlias.'.id = '.$relTable.'.company_id');
                        } else {
                            $queryBuilder->leftJoin(
                                $queryBuilder->getTableAlias('leads'),
                                $filter->getTable(),
                                $tableAlias,
                                sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias('leads'), $tableAlias)
                            );
                        }
                    }
                    break;
                default:
                    //throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
                    dump('Dunno how to handle operator "'.$filterOperator.'"');
            }
        }

        switch ($filterOperator) {
            case 'empty':
                $expression = $queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField());

                break;
            case 'notEmpty':
                $expression = $queryBuilder->expr()->isNotNull($tableAlias.'.'.$filter->getField());
                break;
            case 'startsWith':
            case 'endsWith':
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
            case 'regexp':
            case 'notRegexp':
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
                dump(' * IGNORED * - Dunno how to handle operator "'.$filterOperator.'"');
                //throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
                $expression = '1=1';
        }

        if ($queryBuilder->isJoinTable($filter->getTable())) {
            if ($filterAggr) {
                $queryBuilder->andHaving($expression);
            } else {
                $queryBuilder->addJoinCondition($tableAlias, ' ('.$expression.')');
            }
        } else {
            $queryBuilder->$filterGlueFunc($expression);
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
