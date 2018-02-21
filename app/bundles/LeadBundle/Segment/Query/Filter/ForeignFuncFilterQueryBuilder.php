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
use Mautic\LeadBundle\Segment\Query\QueryException;

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

        try {
            $filter->getColumn();
        } catch (QueryException $e) {
            // We do ignore not found fields as they may be just removed custom field, it's bad!
            return $queryBuilder;
        }

        $filterParametersHolder = $filter->getParameterHolder($parameters);

        $filterGlueFunc = $filterGlue.'Where';

        $tableAlias = $queryBuilder->getTableAlias($filter->getTable());

        // for aggregate function we need to create new alias and not reuse the old one
        if ($filterAggr) {
            $tableAlias = false;
        }

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();

            switch ($filterOperator) {
                case 'notLike':
                case 'notIn':
                case 'startsWith':
                case 'gt':
                case 'eq':
                case 'neq':
                case 'gte':
                case 'like':
                case 'lt':
                case 'lte':
                case 'in':
                    //@TODO this logic needs to
                    if ($filterAggr) {
                        $queryBuilder->leftJoin(
                            $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads'),
                            $filter->getTable(),
                            $tableAlias,
                            sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads'), $tableAlias)
                        );
                    } else {
                        if ($filter->getTable() == 'companies') {
                            $relTable = $this->generateRandomParameterName();
                            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', $relTable, $relTable.'.lead_id = l.id');
                            $queryBuilder->leftJoin($relTable, $filter->getTable(), $tableAlias, $tableAlias.'.id = '.$relTable.'.company_id');
                        } else {
                            $queryBuilder->leftJoin(
                                $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads'),
                                $filter->getTable(),
                                $tableAlias,
                                sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads'), $tableAlias)
                            );
                        }
                    }
                    break;
                default:
                    throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
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
                throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
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

        $queryBuilder->addGroupBy('l.id');

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
