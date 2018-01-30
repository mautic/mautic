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

use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Class SessionsFilterQueryBuilder.
 */
class SessionsFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /** {@inheritdoc} */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.sessions';
    }

    /** {@inheritdoc} */
    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
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

        $tableAlias = $queryBuilder->getTableAlias($filter->getTable());

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();

            $queryBuilder = $queryBuilder->leftJoin(
                $queryBuilder->getTableAlias('leads'),
                MAUTIC_TABLE_PREFIX.$filter->getTable(),
                $tableAlias,
                $tableAlias.'.lead_id = l.id'
            );
        }

        $expression = $queryBuilder->expr()->$filterOperator(
            'count('.$tableAlias.'.id)',
            $filterParametersHolder
        );
        $queryBuilder->addJoinCondition($tableAlias, ' ('.$expression.')');
        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        $queryBuilder->andHaving($expression);

        return $queryBuilder;
    }
}
