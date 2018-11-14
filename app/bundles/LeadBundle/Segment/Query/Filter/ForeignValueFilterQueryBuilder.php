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
            ->select('NULL')->from($filter->getTable(), $tableAlias)
            ->andWhere($tableAlias.'.lead_id = l.id');

        if (!is_null($filter->getWhere())) {
            $subQueryBuilder->andWhere(str_replace(str_replace(MAUTIC_TABLE_PREFIX, '', $filter->getTable()).'.', $tableAlias.'.', $filter->getWhere()));
        }

        switch ($filterOperator) {
            case 'empty':
                $subQueryBuilder->andWhere($subQueryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()));
                $queryBuilder->addLogic($queryBuilder->expr()->exists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'notEmpty':
                $subQueryBuilder->andWhere($subQueryBuilder->expr()->isNotNull($tableAlias.'.'.$filter->getField()));
                $queryBuilder->addLogic($queryBuilder->expr()->exists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'notIn':
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
                $expression = $subQueryBuilder->expr()->orX(
                    $subQueryBuilder->expr()->eq($tableAlias.'.'.$filter->getField(), $filterParametersHolder),
                    $subQueryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField())
                );

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'notLike':
                $expression = $subQueryBuilder->expr()->orX(
                    $subQueryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()),
                    $subQueryBuilder->expr()->like($tableAlias.'.'.$filter->getField(), $filterParametersHolder)
                );

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'regexp':
            case 'notRegexp':
                $not        = ($filterOperator === 'notRegexp') ? ' NOT' : '';
                $expression = $tableAlias.'.'.$filter->getField().$not.' REGEXP '.$filterParametersHolder;

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->exists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            default:
                $expression = $subQueryBuilder->expr()->$filterOperator(
                    $tableAlias.'.'.$filter->getField(),
                    $filterParametersHolder
                );
                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->exists($subQueryBuilder->getSQL()), $filter->getGlue());
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
