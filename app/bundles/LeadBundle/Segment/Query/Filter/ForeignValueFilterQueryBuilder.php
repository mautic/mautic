<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\LeadBatchLimiterTrait;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

class ForeignValueFilterQueryBuilder extends BaseFilterQueryBuilder
{
    use LeadBatchLimiterTrait;

    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.foreign.value';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias  = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $filterOperator   = $filter->getOperator();
        $batchLimiters    = $filter->getBatchLimiters();
        $filterParameters = $filter->getParameterValue();

        // allow use of `contact_id` column instead of deprecated `lead_id`
        $foreignContactColumn = $filter->getForeignContactColumn();

        if (is_array($filterParameters)) {
            $parameters = [];
            foreach ($filterParameters as $filterParameter) {
                $parameters[] = $this->generateRandomParameterName();
            }
        } else {
            $parameters = $this->generateRandomParameterName();
        }

        $filterParametersHolder = $filter->getParameterHolder($parameters);

        $tableAlias = $this->generateRandomParameterName();

        $subQueryBuilder = $queryBuilder->createQueryBuilder();

        if (!is_null($filter->getWhere())) {
            $subQueryBuilder->andWhere(str_replace(str_replace(MAUTIC_TABLE_PREFIX, '', $filter->getTable()).'.', $tableAlias.'.', $filter->getWhere()));
        }

        switch ($filterOperator) {
            case 'empty':
                $subQueryBuilder->select($tableAlias.'.'.$foreignContactColumn)->from($filter->getTable(), $tableAlias);
                $queryBuilder->addLogic($queryBuilder->expr()->notIn($leadsTableAlias.'.id', $subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'notEmpty':
                $subQueryBuilder->select($tableAlias.'.'.$foreignContactColumn)->from($filter->getTable(), $tableAlias);

                $this->addLeadAndMinMaxLimiters($subQueryBuilder, $batchLimiters, str_replace(MAUTIC_TABLE_PREFIX, '', $filter->getTable()), $foreignContactColumn);

                $queryBuilder->addLogic(
                    $queryBuilder->expr()->in($leadsTableAlias.'.id', $subQueryBuilder->getSQL()),
                    $filter->getGlue()
                );
                break;
            case 'notIn':
                $subQueryBuilder
                    ->select('NULL')->from($filter->getTable(), $tableAlias)
                    ->andWhere($tableAlias.'.'.$foreignContactColumn.' = '.$leadsTableAlias.'.id');

                // The use of NOT EXISTS here requires the use of IN instead of NOT IN to prevent a "double negative."
                // We are not using EXISTS...NOT IN because it results in including everyone who has at least one entry that doesn't
                // match the criteria. For example, with tags, if the contact has the tag in the filter but also another tag, they'll
                // be included in the results which is not what we want.
                $expression = $subQueryBuilder->expr()->in(
                    $tableAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );

                $subQueryBuilder->andWhere($expression);
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'neq':
                $subQueryBuilder
                    ->select('NULL')->from($filter->getTable(), $tableAlias)
                    ->andWhere($tableAlias.'.'.$foreignContactColumn.' = '.$leadsTableAlias.'.id');

                $expression = $subQueryBuilder->expr()->or(
                    $subQueryBuilder->expr()->eq($tableAlias.'.'.$filter->getField(), $filterParametersHolder),
                    $subQueryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField())
                );

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'notLike':
                $subQueryBuilder
                    ->select('NULL')->from($filter->getTable(), $tableAlias)
                    ->andWhere($tableAlias.'.'.$foreignContactColumn.' = '.$leadsTableAlias.'.id');

                $expression = $subQueryBuilder->expr()->or(
                    $subQueryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()),
                    $subQueryBuilder->expr()->like($tableAlias.'.'.$filter->getField(), $filterParametersHolder)
                );

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'regexp':
            case 'notRegexp':
                $subQueryBuilder->select($tableAlias.'.'.$foreignContactColumn)
                    ->from($filter->getTable(), $tableAlias);

                $this->addLeadAndMinMaxLimiters($subQueryBuilder, $batchLimiters, str_replace(MAUTIC_TABLE_PREFIX, '', $filter->getTable()), $foreignContactColumn);

                $not        = ('notRegexp' === $filterOperator) ? ' NOT' : '';
                $expression = $tableAlias.'.'.$filter->getField().$not.' REGEXP '.$filterParametersHolder;

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic(
                    $queryBuilder->expr()->in($leadsTableAlias.'.id', $subQueryBuilder->getSQL()),
                    $filter->getGlue()
                );
                break;
            default:
                $subQueryBuilder->select($tableAlias.'.'.$foreignContactColumn)
                    ->from($filter->getTable(), $tableAlias);

                $this->addLeadAndMinMaxLimiters($subQueryBuilder, $batchLimiters, str_replace(MAUTIC_TABLE_PREFIX, '', $filter->getTable()), $foreignContactColumn);

                $expression = $subQueryBuilder->expr()->$filterOperator(
                    $tableAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->in($leadsTableAlias.'.id', $subQueryBuilder->getSQL()), $filter->getGlue());
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
