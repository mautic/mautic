<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Used to connect foreign tables using third table.
 *
 * Currently only company decorator uses this functionality but it may be used by plugins in the future
 *
 * filter decorator must implement methods:
 *  $filter->getRelationJoinTable()
 *  $filter->getRelationJoinTableField()
 *
 * @see \Mautic\LeadBundle\Segment\Decorator\CompanyDecorator
 */
class ComplexRelationValueFilterQueryBuilder extends BaseFilterQueryBuilder
{
    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.complex_relation.value';
    }

    /**
     * @throws \Exception
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $filterOperator  = $filter->getOperator();

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

            $relTable = $this->generateRandomParameterName();
            $queryBuilder->leftJoin($leadsTableAlias, $filter->getRelationJoinTable(), $relTable, $relTable.'.lead_id = '.$leadsTableAlias.'.id');
            $queryBuilder->leftJoin($relTable, $filter->getTable(), $tableAlias, $tableAlias.'.id = '.$relTable.'.'
                .$filter->getRelationJoinTableField());
        }

        switch ($filterOperator) {
            case 'empty':
                $expression = new CompositeExpression(CompositeExpression::TYPE_OR,
                    [
                        $queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()),
                        $queryBuilder->expr()->eq($tableAlias.'.'.$filter->getField(), $queryBuilder->expr()->literal('')),
                    ]
                );
                break;
            case 'notEmpty':
                $expression = new CompositeExpression(CompositeExpression::TYPE_AND,
                    [
                        $queryBuilder->expr()->isNotNull($tableAlias.'.'.$filter->getField()),
                        $queryBuilder->expr()->neq($tableAlias.'.'.$filter->getField(), $queryBuilder->expr()->literal('')),
                    ]
                );

                break;
            case 'neq':
                // Special handling for boolean neq
                if ($filter->isColumnTypeBoolean()) {
                    // Boolean != NO (0) means "YES" (exactly 1)
                    if (false === $filterParameters || 0 === $filterParameters || '0' === $filterParameters) {
                        $expression = $queryBuilder->expr()->eq(
                            $tableAlias.'.'.$filter->getField(),
                            $queryBuilder->expr()->literal('1')
                        );
                    }
                    // Boolean != YES (1) means "not YES" (not 1) - includes 0 and NULL
                    elseif (true === $filterParameters || 1 === $filterParameters || '1' === $filterParameters) {
                        $expression = $queryBuilder->expr()->neq(
                            $tableAlias.'.'.$filter->getField(),
                            $queryBuilder->expr()->literal('1')
                        );
                    }
                } else {
                    // For non-boolean fields, include NULL in neq operations
                    $expression = $queryBuilder->expr()->or(
                        $queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()),
                        $queryBuilder->expr()->$filterOperator(
                            $tableAlias.'.'.$filter->getField(),
                            $filterParametersHolder
                        )
                    );
                }
                break;
            case 'startsWith':
            case 'endsWith':
            case 'gt':
            case 'gte':
            case 'like':
            case 'lt':
            case 'lte':
            case 'in':
            case OperatorOptions::INCLUDING_ANY:
                $expression = $queryBuilder->expr()->in(
                    $tableAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                break;
            case 'eq':
                // Special handling for boolean eq
                if ($filter->isColumnTypeBoolean()) {
                    // Boolean = NO (0) means "not YES" (not 1) - includes 0 and NULL
                    if (false === $filterParameters || 0 === $filterParameters || '0' === $filterParameters) {
                        $expression = $queryBuilder->expr()->neq(
                            $tableAlias.'.'.$filter->getField(),
                            $queryBuilder->expr()->literal('1')
                        );
                    }
                    // Boolean = YES (1) means exactly 1
                    elseif (true === $filterParameters || 1 === $filterParameters || '1' === $filterParameters) {
                        $expression = $queryBuilder->expr()->eq(
                            $tableAlias.'.'.$filter->getField(),
                            $queryBuilder->expr()->literal('1')
                        );
                    }
                } else {
                    $expression = $queryBuilder->expr()->$filterOperator(
                        $tableAlias.'.'.$filter->getField(),
                        $filterParametersHolder
                    );
                }
                break;
            case OperatorOptions::INCLUDING_ALL:
                // Note: INCLUDING_ALL for complex relations may not be mathematically meaningful
                // as it implies a single field value matches ALL provided values simultaneously
                // This is likely a configuration error, but we'll treat it as INCLUDING_ANY
                $expression = $queryBuilder->expr()->in(
                    $tableAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                break;
            case 'between':   // Used only for date with week combination (EQUAL [this week, next week, last week])
            case 'regexp':
            case 'notRegexp': // Different behaviour from 'notLike' because of BC (do not use condition for NULL). Could be changed in Mautic 3.
                $expression = $queryBuilder->expr()->$filterOperator(
                    $tableAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                break;
            case 'notLike':
            case 'notBetween': // Used only for date with week combination (NOT EQUAL [this week, next week, last week])
            case 'notIn':
                $expression = $queryBuilder->expr()->or(
                    $queryBuilder->expr()->$filterOperator($tableAlias.'.'.$filter->getField(), $filterParametersHolder),
                    $queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField())
                );
                break;
            case 'multiselect':
            case '!multiselect':
                $operator    = 'multiselect' === $filterOperator ? 'regexp' : 'notRegexp';
                $expressions = [];
                foreach ($filterParametersHolder as $parameter) {
                    $expressions[] = $queryBuilder->expr()->$operator($tableAlias.'.'.$filter->getField(), $parameter);
                }

                $expression = $queryBuilder->expr()->and(...$expressions);
                break;
            default:
                throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
        }

        $queryBuilder->addLogic($expression, $filter->getGlue());

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
