<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

class AnyCompanyRelationValueFilterQueryBuilder extends PrimaryCompanyRelationValueFilterQueryBuilder
{
    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.complex_relation.any_company';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias  = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $filterOperator   = $filter->getOperator();
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

        $this->applyCompanyFilterExpression($subQueryBuilder, $filter, $filterOperator, $filterParametersHolder, $companyAlias);

        $existsExpression    = $queryBuilder->expr()->exists($subQueryBuilder->getSQL());
        $allowMissingCompany = in_array($filterOperator, ['empty', 'neq', 'notLike', 'notBetween', 'notIn'], true)
            && !$filter->contactSegmentFilterCrate->isCompanyAllType();

        if ($allowMissingCompany) {
            $relationExistsAlias = $this->generateRandomParameterName();
            $relationExistsQuery = $queryBuilder->createQueryBuilder();
            $relationExistsQuery->select('1')
                ->from($filter->getRelationJoinTable(), $relationExistsAlias)
                ->andWhere($relationExistsQuery->expr()->eq($relationExistsAlias.'.lead_id', $leadsTableAlias.'.id'));

            $existsExpression = $queryBuilder->expr()->or(
                $existsExpression,
                $queryBuilder->expr()->notExists($relationExistsQuery->getSQL())
            );
        }

        $queryBuilder->addLogic($existsExpression, $filter->getGlue());
        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
