<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\Exception\UnsupportedFilterOperatorException;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

class PrimaryCompanyRelationValueFilterQueryBuilder extends ComplexRelationValueFilterQueryBuilder
{
    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.complex_relation.primary_company';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias  = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $filterOperator   = $filter->getOperator();
        $filterParameters = $filter->getParameterValue();
        $parameters       = $this->buildParameters($filterParameters);

        $filterParametersHolder = $filter->getParameterHolder($parameters);

        $relationAlias   = $this->generateRandomParameterName();
        $companyAlias    = $this->generateRandomParameterName();
        $subQueryBuilder = $queryBuilder->createQueryBuilder();
        $subQueryBuilder->select('1')
            ->from($filter->getRelationJoinTable(), $relationAlias)
            ->leftJoin(
                $relationAlias,
                $filter->getTable(),
                $companyAlias,
                $companyAlias.'.id = '.$relationAlias.'.'.$filter->getRelationJoinTableField()
            )
            ->andWhere($subQueryBuilder->expr()->eq($relationAlias.'.lead_id', $leadsTableAlias.'.id'));

        $this->addRelationScopeCondition($subQueryBuilder, $relationAlias);

        $this->applyCompanyFilterExpression($subQueryBuilder, $filter, $filterOperator, $filterParametersHolder, $companyAlias);

        $existsExpression = $queryBuilder->expr()->exists($subQueryBuilder->getSQL());

        if ($this->shouldAllowMissingCompany($filterOperator)) {
            $relationExistsQuery = $this->createRelationExistsQuery($queryBuilder, $filter, $leadsTableAlias);

            $existsExpression = $queryBuilder->expr()->or(
                $existsExpression,
                $queryBuilder->expr()->notExists($relationExistsQuery->getSQL())
            );
        }

        $queryBuilder->addLogic($existsExpression, $filter->getGlue());
        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }

    protected function shouldAllowMissingCompany(string $filterOperator): bool
    {
        return in_array($filterOperator, ['empty', 'neq', 'notLike', 'notBetween', 'notIn'], true);
    }

    protected function requiresPrimaryCompany(): bool
    {
        return true;
    }

    private function createRelationExistsQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter, string $leadsTableAlias): QueryBuilder
    {
        $relationAlias = $this->generateRandomParameterName();
        $relationQuery = $queryBuilder->createQueryBuilder();
        $relationQuery->select('1')
            ->from($filter->getRelationJoinTable(), $relationAlias)
            ->andWhere($relationQuery->expr()->eq($relationAlias.'.lead_id', $leadsTableAlias.'.id'));

        $this->addRelationScopeCondition($relationQuery, $relationAlias);

        return $relationQuery;
    }

    private function addRelationScopeCondition(QueryBuilder $queryBuilder, string $relationAlias): void
    {
        if ($this->requiresPrimaryCompany()) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq($relationAlias.'.is_primary', 1));
        }
    }

    protected function applyCompanyFilterExpression(
        QueryBuilder $subQueryBuilder,
        ContactSegmentFilter $filter,
        string $filterOperator,
        mixed $filterParametersHolder,
        string $companyAlias,
    ): void {
        switch ($filterOperator) {
            case 'empty':
                $expression = new CompositeExpression(CompositeExpression::TYPE_OR,
                    [
                        $subQueryBuilder->expr()->isNull($companyAlias.'.'.$filter->getField()),
                        $subQueryBuilder->expr()->eq($companyAlias.'.'.$filter->getField(), $subQueryBuilder->expr()->literal('')),
                    ]
                );
                break;
            case 'notEmpty':
                $expression = new CompositeExpression(CompositeExpression::TYPE_AND,
                    [
                        $subQueryBuilder->expr()->isNotNull($companyAlias.'.'.$filter->getField()),
                        $subQueryBuilder->expr()->neq($companyAlias.'.'.$filter->getField(), $subQueryBuilder->expr()->literal('')),
                    ]
                );
                break;
            case 'neq':
                $expression = $subQueryBuilder->expr()->or(
                    $subQueryBuilder->expr()->isNull($companyAlias.'.'.$filter->getField()),
                    $subQueryBuilder->expr()->$filterOperator(
                        $companyAlias.'.'.$filter->getField(),
                        $filterParametersHolder
                    )
                );
                break;
            case 'startsWith':
            case 'endsWith':
                $expression = $subQueryBuilder->expr()->like(
                    $companyAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                break;
            case 'gt':
            case 'eq':
            case 'gte':
            case 'like':
            case 'lt':
            case 'lte':
            case 'in':
            case 'between':
            case 'regexp':
            case 'notRegexp':
                $expression = $subQueryBuilder->expr()->$filterOperator(
                    $companyAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                break;
            case 'notLike':
            case 'notBetween':
            case 'notIn':
                $expression = $subQueryBuilder->expr()->or(
                    $subQueryBuilder->expr()->$filterOperator($companyAlias.'.'.$filter->getField(), $filterParametersHolder),
                    $subQueryBuilder->expr()->isNull($companyAlias.'.'.$filter->getField())
                );
                break;
            case 'multiselect':
            case '!multiselect':
                $operator    = 'multiselect' === $filterOperator ? 'regexp' : 'notRegexp';
                $expressions = [];
                foreach ($filterParametersHolder as $parameter) {
                    $expressions[] = $subQueryBuilder->expr()->$operator($companyAlias.'.'.$filter->getField(), $parameter);
                }

                $expression = $subQueryBuilder->expr()->and(...$expressions);
                break;
            default:
                throw UnsupportedFilterOperatorException::fromOperator($filterOperator);
        }

        $subQueryBuilder->andWhere($expression);
    }
}
