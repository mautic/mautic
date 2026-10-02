<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\Filter\Exception\UnsupportedFilterOperatorException;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Base implementation reused by any-company filters that only relax the relation scope.
 */
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
        return in_array($filterOperator, ['empty', 'neq', 'notLike', 'notBetween', 'notIn', '!multiselect', OperatorOptions::EXCLUDING_ALL], true);
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
        $expression = match ($filterOperator) {
            'empty'                 => $this->getEmptyExpression($subQueryBuilder, $filter, $companyAlias),
            'notEmpty'              => $this->getNotEmptyExpression($subQueryBuilder, $filter, $companyAlias),
            'neq'                   => $this->getNotEqualExpression($subQueryBuilder, $filter, $filterParametersHolder, $companyAlias),
            'startsWith', 'endsWith'=> $this->getLikeExpression($subQueryBuilder, $filter, $filterParametersHolder, $companyAlias),
            'gt',
            'eq',
            'gte',
            'like',
            'lt',
            'lte',
            'in',
            'between',
            'inLast',
            'inNext',
            'regexp',
            'notRegexp'             => $this->getOperatorExpression($subQueryBuilder, $filter, $filterOperator, $filterParametersHolder, $companyAlias),
            'notLike',
            'notBetween',
            'notIn'                 => $this->getNullableNegativeExpression($subQueryBuilder, $filter, $filterOperator, $filterParametersHolder, $companyAlias),
            'multiselect',
            '!multiselect'                 => $this->getMultiselectExpression($subQueryBuilder, $filter, $filterParametersHolder, $companyAlias),
            OperatorOptions::INCLUDING_ALL => $this->getIncludingAllExpression($subQueryBuilder, $filter, $filterParametersHolder, $companyAlias),
            OperatorOptions::EXCLUDING_ALL => $this->getExcludingAllExpression($subQueryBuilder, $filter, $filterParametersHolder, $companyAlias),
            default                        => throw UnsupportedFilterOperatorException::fromOperator($filterOperator),
        };

        $subQueryBuilder->andWhere($expression);
    }

    private function getEmptyExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, string $companyAlias): CompositeExpression
    {
        return new CompositeExpression(CompositeExpression::TYPE_OR,
            [
                $subQueryBuilder->expr()->isNull($this->getCompanyField($filter, $companyAlias)),
                $subQueryBuilder->expr()->eq($this->getCompanyField($filter, $companyAlias), $subQueryBuilder->expr()->literal('')),
            ]
        );
    }

    private function getNotEmptyExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, string $companyAlias): CompositeExpression
    {
        return new CompositeExpression(CompositeExpression::TYPE_AND,
            [
                $subQueryBuilder->expr()->isNotNull($this->getCompanyField($filter, $companyAlias)),
                $subQueryBuilder->expr()->neq($this->getCompanyField($filter, $companyAlias), $subQueryBuilder->expr()->literal('')),
            ]
        );
    }

    private function getLikeExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, mixed $filterParametersHolder, string $companyAlias): string
    {
        return $subQueryBuilder->expr()->like(
            $this->getCompanyField($filter, $companyAlias),
            $filterParametersHolder
        );
    }

    private function getOperatorExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, string $filterOperator, mixed $filterParametersHolder, string $companyAlias): string
    {
        return $subQueryBuilder->expr()->$filterOperator(
            $this->getCompanyField($filter, $companyAlias),
            $filterParametersHolder
        );
    }

    private function getNullableNegativeExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, string $filterOperator, mixed $filterParametersHolder, string $companyAlias): CompositeExpression
    {
        return $subQueryBuilder->expr()->or(
            $subQueryBuilder->expr()->$filterOperator($this->getCompanyField($filter, $companyAlias), $filterParametersHolder),
            $subQueryBuilder->expr()->isNull($this->getCompanyField($filter, $companyAlias))
        );
    }

    private function getNotEqualExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, mixed $filterParametersHolder, string $companyAlias): CompositeExpression
    {
        return $subQueryBuilder->expr()->or(
            $subQueryBuilder->expr()->isNull($this->getCompanyField($filter, $companyAlias)),
            $subQueryBuilder->expr()->neq($this->getCompanyField($filter, $companyAlias), $filterParametersHolder)
        );
    }

    private function getMultiselectExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, mixed $filterParametersHolder, string $companyAlias): CompositeExpression|string
    {
        $filterArray      = $filter->contactSegmentFilterCrate->getArray();
        $originalOperator = $filterArray['operator'];
        $applyIsNull      = in_array($originalOperator, [OperatorOptions::EXCLUDING_ALL, OperatorOptions::EXCLUDING_ANY], true);
        $applyNot         = OperatorOptions::EXCLUDING_ALL === $originalOperator;
        $operator         = OperatorOptions::EXCLUDING_ANY === $originalOperator ? 'notRegexp' : 'regexp';
        $filterGlue       = in_array($originalOperator, [OperatorOptions::INCLUDING_ALL, OperatorOptions::EXCLUDING_ALL, OperatorOptions::EXCLUDING_ANY], true) ? 'and' : 'or';

        $expressions = [];
        foreach ((array) $filterParametersHolder as $parameter) {
            $expressions[] = $subQueryBuilder->expr()->$operator($this->getCompanyField($filter, $companyAlias), $parameter);
        }

        return $this->combineMultiselectExpressions($subQueryBuilder, $filter, $companyAlias, $expressions, $filterGlue, $applyIsNull, $applyNot);
    }

    /**
     * @param string[] $expressions
     */
    private function combineMultiselectExpressions(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, string $companyAlias, array $expressions, string $filterGlue, bool $applyIsNull, bool $applyNot): CompositeExpression|string
    {
        if ([] === $expressions) {
            return $subQueryBuilder->expr()->and($applyIsNull ? '1 = 1' : '1 = 0');
        }

        if (!$applyIsNull) {
            return $subQueryBuilder->expr()->$filterGlue(...$expressions);
        }

        $expression = $subQueryBuilder->expr()->$filterGlue(...$expressions);
        if ($applyNot) {
            $expression = 'NOT('.$expression.')';
        }

        return $subQueryBuilder->expr()->or(
            $expression,
            $subQueryBuilder->expr()->isNull($this->getCompanyField($filter, $companyAlias))
        );
    }

    private function getIncludingAllExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, mixed $filterParametersHolder, string $companyAlias): CompositeExpression|string
    {
        if (is_array($filterParametersHolder) && count($filterParametersHolder) > 1) {
            return $subQueryBuilder->expr()->and('1 = 0');
        }

        return $subQueryBuilder->expr()->eq(
            $this->getCompanyField($filter, $companyAlias),
            is_array($filterParametersHolder) ? $filterParametersHolder[0] : $filterParametersHolder
        );
    }

    private function getExcludingAllExpression(QueryBuilder $subQueryBuilder, ContactSegmentFilter $filter, mixed $filterParametersHolder, string $companyAlias): CompositeExpression
    {
        if (is_array($filterParametersHolder) && count($filterParametersHolder) > 1) {
            return $subQueryBuilder->expr()->and('1 = 1');
        }

        return $subQueryBuilder->expr()->or(
            $subQueryBuilder->expr()->isNull($this->getCompanyField($filter, $companyAlias)),
            $subQueryBuilder->expr()->neq(
                $this->getCompanyField($filter, $companyAlias),
                is_array($filterParametersHolder) ? $filterParametersHolder[0] : $filterParametersHolder
            )
        );
    }

    private function getCompanyField(ContactSegmentFilter $filter, string $companyAlias): string
    {
        return $companyAlias.'.'.$filter->getField();
    }
}
