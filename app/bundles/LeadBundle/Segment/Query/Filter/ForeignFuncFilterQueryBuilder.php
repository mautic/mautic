<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Class ForeignFuncFilterQueryBuilder.
 */
class ForeignFuncFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * {@inheritdoc}
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.foreign.func';
    }

    /**
     * {@inheritdoc}
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
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

        // Check if the column exists in the table
        $filter->getColumn();

        $filterParametersHolder = $filter->getParameterHolder($parameters);

        $filterGlueFunc = $filterGlue.'Where';

        $tableAlias = $queryBuilder->getTableAlias($filter->getTable());

        // for aggregate function we need to create new alias and not reuse the old one
        if ($filterAggr) {
            $tableAlias = false;
        }

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();
            if ($filterAggr) {
                // No join needed, it is placed in exist/not exists
            } else {
                if ($filter->getTable() == 'companies') {
                    $relTable = $this->generateRandomParameterName();
                    $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', $relTable, $relTable.'.lead_id = l.id');
                    $queryBuilder->leftJoin($relTable, $filter->getTable(), $tableAlias, $tableAlias.'.id = '.$relTable.'.company_id');
                } else { // This should never happen
                    $queryBuilder->leftJoin(
                        $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads'),
                        $filter->getTable(),
                        $tableAlias,
                        sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads'), $tableAlias)
                    );
                }
            }
        }

        switch ($filterOperator) {
            case 'empty':
                $expression = $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()),
                    $queryBuilder->expr()->eq($tableAlias.'.'.$filter->getField(), ':'.$emptyParameter = $this->generateRandomParameterName())
                );
                $queryBuilder->setParameter($emptyParameter, '');
                break;
            case 'notEmpty':
                $expression = $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->isNotNull($tableAlias.'.'.$filter->getField()),
                    $queryBuilder->expr()->neq($tableAlias.'.'.$filter->getField(), ':'.$emptyParameter = $this->generateRandomParameterName())
                );
                $queryBuilder->setParameter($emptyParameter, '');
                break;
            default:
                if ($filterAggr) {
                    if (!is_null($filter)) {
                        if ($filterAggr === 'sum') {
                            $expressionArg = $queryBuilder->expr()->func('COALESCE',
                                $queryBuilder->expr()->func('SUM', $tableAlias.'.'.$filter->getField()),
                                0
                            );
                            $expression = $queryBuilder->expr()->$filterOperator($expressionArg,
                                $filterParametersHolder);
                        } else {
                            $expressionArg = sprintf('%s(DISTINCT %s)', $filterAggr, $tableAlias.'.'
                                .$filter->getField());
                            $expression = $queryBuilder->expr()->$filterOperator(
                                $expressionArg,
                                $filterParametersHolder
                            );
                        }
                    } else {
                        $expressionArg = $queryBuilder->expr()->func($filterAggr, $tableAlias.'.'.$filter->getField());
                        $expression    = $queryBuilder->expr()->$filterOperator(
                            $expressionArg,
                            $filterParametersHolder
                        );
                    }
                } else { // This should never happen
                    $expression = $queryBuilder->expr()->$filterOperator(
                        $tableAlias.'.'.$filter->getField(),
                        $filterParametersHolder
                    );
                }
                break;
        }

        if ($queryBuilder->isJoinTable($filter->getTable()) && !$filterAggr) { // This should never happen
            $queryBuilder->addJoinCondition($tableAlias, ' ('.$expression.')');
            $queryBuilder->addGroupBy('l.id');
        } else {
            if ($filterAggr) {
                $expression = $queryBuilder->expr()->exists('SELECT '.$expressionArg.' FROM '.$filter->getTable().' '.
                    $tableAlias.' WHERE l.id='.$tableAlias.'.lead_id HAVING '.$expression);
            } else { // This should never happen
                $queryBuilder->addGroupBy('l.id');
            }

            $queryBuilder->addLogic($expression, $filter->getGlue());
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
