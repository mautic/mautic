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
 * Class ForeignValueFilterQueryBuilder.
 */
class ForeignValueFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /** {@inheritdoc} */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.foreign.value';
    }

    /** {@inheritdoc} */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();

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

        $tableAlias = $this->generateRandomParameterName();

        $subQueryBuilder = $queryBuilder->getConnection()->createQueryBuilder();
        $subQueryBuilder
            ->select($tableAlias.'.lead_id')->from($filter->getTable(), $tableAlias);

        if (!is_null($filter->getWhere())) {
            $subQueryBuilder->andWhere(str_replace(str_replace(MAUTIC_TABLE_PREFIX, '', $filter->getTable()).'.', $tableAlias.'.', $filter->getWhere()));
        }

        switch ($filterOperator) {
            case 'empty':
            case 'notEmpty':
                //Using a join table is necessary for empty/not empty to work properly
                //An Exists or IN subquery would not provide the proper result if there is no record for the lead in the foreign table
                //If we join the foreign table by lead_id and check for the filtered field for Null/notnull, the result will contain both those leads that have no record in the foreign table and those that have but with the value of NULL
                //Most often if a lead has not or has not done something that is usually stored in a foreign table, there is no record for that data
                //As empty/not empty you want to check whether that record is present or not

                $tableAlias = $this->generateRandomParameterName();

                $queryBuilder->leftJoin(
                    $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads'),
                    $filter->getTable(),
                    $tableAlias,
                    $tableAlias.'.lead_id = l.id'
                );

                if ($filterOperator == 'empty') {
                    $queryBuilder->addLogic($queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()), $filter->getGlue());
                } else {
                    $queryBuilder->addLogic($queryBuilder->expr()->isNotNull($tableAlias.'.'.$filter->getField()), $filter->getGlue());
                }
                break;
            case 'notIn':
                $expression = $subQueryBuilder->expr()->andX(
                    $subQueryBuilder->expr()->in($tableAlias.'.'.$filter->getField(), $filterParametersHolder),
                    $subQueryBuilder->expr()->isNotNull($tableAlias.'.lead_id')
                );

                $subQueryBuilder->andWhere($expression);
                $queryBuilder->addLogic($queryBuilder->expr()->notIn('l.id', $subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'neq':
                $expression = $subQueryBuilder->expr()->andX(
                    $subQueryBuilder->expr()->eq($tableAlias.'.'.$filter->getField(), $filterParametersHolder),
                    $subQueryBuilder->expr()->isNotNull($tableAlias.'.lead_id')
                );

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notIn('l.id', $subQueryBuilder->getSQL()), $filter->getGlue());

                break;
            case 'notLike':
                $expression = $subQueryBuilder->expr()->andX(
                    $subQueryBuilder->expr()->like($tableAlias.'.'.$filter->getField(), $filterParametersHolder),
                    $subQueryBuilder->expr()->isNotNull($tableAlias.'.lead_id')
                );

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notIn('l.id', $subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'regexp':
                $expression = $tableAlias.'.'.$filter->getField().' REGEXP '.$filterParametersHolder;

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->in('l.id', $subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'notRegexp':
                $expression = $subQueryBuilder->expr()->andX(
                    $tableAlias.'.'.$filter->getField().'  REGEXP '.$filterParametersHolder,
                    $subQueryBuilder->expr()->isNotNull($tableAlias.'.lead_id')
                );

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notIn('l.id', $subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            default:
                $expression = $subQueryBuilder->expr()->$filterOperator(
                    $tableAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->in('l.id', $subQueryBuilder->getSQL()), $filter->getGlue());
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
