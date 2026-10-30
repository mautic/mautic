<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\Filter\Exception\UnsupportedFilterOperatorException;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Base implementation reused by any-company tag filters that only relax the relation scope.
 */
class PrimaryCompanyTagRelationValueFilterQueryBuilder extends BaseFilterQueryBuilder
{
    private const LEAD_ID_COLUMN = '.lead_id';

    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.complex_relation.primary_company_tag';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias  = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $filterOperator   = $filter->getOperator();
        $filterParameters = $filter->getParameterValue();
        $requiresParams   = !$this->operatorDoesNotNeedParameters($filterOperator);
        $parameters       = $requiresParams ? $this->buildParameters($filterParameters) : [];

        $filterParametersHolder = null;
        if ($requiresParams) {
            $filterParametersHolder = $filter->getParameterHolder($parameters);
        }

        $tagFilterQuery = $this->createTagFilterQuery($queryBuilder, $filter, $filterOperator, $filterParametersHolder, $leadsTableAlias);

        $expression = $this->operatorShouldUseNotExists($filterOperator)
            ? $queryBuilder->expr()->notExists($tagFilterQuery->getSQL())
            : $queryBuilder->expr()->exists($tagFilterQuery->getSQL());

        if ($this->operatorShouldUseNotExists($filterOperator) && !$this->shouldAllowMissingCompany($filterOperator)) {
            $relationExistsQuery = $this->createRelationExistsQuery($queryBuilder, $filter, $leadsTableAlias);
            $expression          = $queryBuilder->expr()->and(
                $expression,
                $queryBuilder->expr()->exists($relationExistsQuery->getSQL())
            );
        }

        $queryBuilder->addLogic($expression, $filter->getGlue());

        if ($requiresParams) {
            $queryBuilder->setParametersPairs($parameters, $filterParameters);
        }

        return $queryBuilder;
    }

    protected function shouldAllowMissingCompany(string $filterOperator): bool
    {
        return in_array($filterOperator, ['empty', 'neq', 'notLike', 'notBetween', 'notIn', OperatorOptions::EXCLUDING_ALL], true);
    }

    protected function requiresPrimaryCompany(): bool
    {
        return true;
    }

    /**
     * @return array<string>|string
     */
    protected function buildParameters(mixed $filterParameters): array|string
    {
        if (!is_array($filterParameters)) {
            return $this->generateRandomParameterName();
        }

        return array_map($this->generateRandomParameterName(...), $filterParameters);
    }

    private function createTagFilterQuery(
        QueryBuilder $queryBuilder,
        ContactSegmentFilter $filter,
        string $filterOperator,
        mixed $filterParametersHolder,
        string $leadsTableAlias,
    ): QueryBuilder {
        $relationAlias   = $this->generateRandomParameterName();
        $tagAlias        = $this->generateRandomParameterName();
        $subQueryBuilder = $queryBuilder->createQueryBuilder();
        $subQueryBuilder->select('1')
            ->from($filter->getRelationJoinTable(), $relationAlias)
            ->leftJoin(
                $relationAlias,
                $filter->getTable(),
                $tagAlias,
                $tagAlias.'.company_id = '.$relationAlias.'.'.$filter->getRelationJoinTableField()
            )
            ->andWhere($subQueryBuilder->expr()->eq($relationAlias.self::LEAD_ID_COLUMN, $leadsTableAlias.'.id'));

        $this->addRelationScopeCondition($subQueryBuilder, $relationAlias);
        $this->applyTagFilterOperator($subQueryBuilder, $filterOperator, $filterParametersHolder, $tagAlias, $filter->getField(), $relationAlias);

        return $subQueryBuilder;
    }

    private function applyTagFilterOperator(
        QueryBuilder $subQueryBuilder,
        string $filterOperator,
        mixed $filterParametersHolder,
        string $tagAlias,
        string $field,
        string $relationAlias,
    ): void {
        switch ($filterOperator) {
            case 'empty':
            case 'notEmpty':
                $subQueryBuilder->andWhere($subQueryBuilder->expr()->isNotNull($tagAlias.'.'.$field));
                break;
            case 'eq':
            case 'neq':
                if (is_array($filterParametersHolder)) {
                    $subQueryBuilder->andWhere($subQueryBuilder->expr()->in($tagAlias.'.'.$field, $filterParametersHolder));
                    break;
                }

                $subQueryBuilder->andWhere($subQueryBuilder->expr()->eq($tagAlias.'.'.$field, $filterParametersHolder));
                break;
            case 'in':
            case 'notIn':
                $subQueryBuilder->andWhere($subQueryBuilder->expr()->in($tagAlias.'.'.$field, $filterParametersHolder));
                break;
            case 'like':
            case 'notLike':
                $subQueryBuilder->andWhere($subQueryBuilder->expr()->like($tagAlias.'.'.$field, $filterParametersHolder));
                break;
            case 'between':
            case 'notBetween':
                if (!is_array($filterParametersHolder) || 2 !== count($filterParametersHolder)) {
                    throw UnsupportedFilterOperatorException::fromOperator($filterOperator);
                }

                $subQueryBuilder->andWhere(
                    $subQueryBuilder->expr()->between(
                        $tagAlias.'.'.$field,
                        [$filterParametersHolder[0], $filterParametersHolder[1]]
                    )
                );
                break;
            case 'regexp':
            case 'notRegexp':
                $subQueryBuilder->andWhere($tagAlias.'.'.$field.' REGEXP '.$filterParametersHolder);
                break;
            case OperatorOptions::INCLUDING_ALL:
            case OperatorOptions::EXCLUDING_ALL:
                if (!is_array($filterParametersHolder)) {
                    throw UnsupportedFilterOperatorException::fromOperator($filterOperator);
                }

                $subQueryBuilder->andWhere($subQueryBuilder->expr()->in($tagAlias.'.'.$field, $filterParametersHolder));
                $subQueryBuilder->groupBy($relationAlias.self::LEAD_ID_COLUMN);
                $subQueryBuilder->having('COUNT(DISTINCT '.$tagAlias.'.'.$field.') = '.count($filterParametersHolder));
                break;
            default:
                throw UnsupportedFilterOperatorException::fromOperator($filterOperator);
        }
    }

    private function createRelationExistsQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter, string $leadsTableAlias): QueryBuilder
    {
        $relationAlias = $this->generateRandomParameterName();
        $relationQuery = $queryBuilder->createQueryBuilder();
        $relationQuery->select('1')
            ->from($filter->getRelationJoinTable(), $relationAlias)
            ->andWhere($relationQuery->expr()->eq($relationAlias.self::LEAD_ID_COLUMN, $leadsTableAlias.'.id'));

        $this->addRelationScopeCondition($relationQuery, $relationAlias);

        return $relationQuery;
    }

    private function addRelationScopeCondition(QueryBuilder $queryBuilder, string $relationAlias): void
    {
        if ($this->requiresPrimaryCompany()) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq($relationAlias.'.is_primary', 1));
        }
    }

    private function operatorDoesNotNeedParameters(string $filterOperator): bool
    {
        return in_array($filterOperator, ['empty', 'notEmpty'], true);
    }

    private function operatorShouldUseNotExists(string $filterOperator): bool
    {
        return in_array($filterOperator, ['empty', 'neq', 'notLike', 'notBetween', 'notIn', OperatorOptions::EXCLUDING_ALL], true);
    }
}
