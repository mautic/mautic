<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 1/11/18
 * Time: 11:24 AM.
 */

namespace Mautic\LeadBundle\Segment\FilterQueryBuilder;

use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

class SessionsFilterQueryBuilder extends BaseFilterQueryBuilder
{
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.sessions';
    }

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
                $filter->getTable(),
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

//$operand = 'EXISTS';
//$table   = 'page_hits';
//$select  = 'COUNT(id)';
//$subqb   = $this->entityManager->getConnection()->createQueryBuilder()->select($select)
//                               ->from(MAUTIC_TABLE_PREFIX.$table, $alias);
//
//$alias2 = $this->generateRandomParameterName();
//$subqb2 = $this->entityManager->getConnection()->createQueryBuilder()->select($alias2.'.id')
//                              ->from(MAUTIC_TABLE_PREFIX.$table, $alias2);
//
//$subqb2->where($q->expr()->andX($q->expr()->eq($alias2.'.lead_id', 'l.id'), $q->expr()
//                                                                              ->gt($alias2.'.date_hit', '('.$alias.'.date_hit - INTERVAL 30 MINUTE)'), $q->expr()
//                                                                                                                                                         ->lt($alias2.'.date_hit', $alias.'.date_hit')));
//
//$parameters[$parameter] = $leadSegmentFilter->getFilter();
//
//$subqb->where($q->expr()->andX($q->expr()->eq($alias.'.lead_id', 'l.id'), $q->expr()
//                                                                            ->isNull($alias.'.email_id'), $q->expr()
//                                                                                                            ->isNull($alias.'.redirect_id'), sprintf('%s (%s)', 'NOT EXISTS', $subqb2->getSQL())));
//
//$opr = '';
//switch ($func) {
//    case 'eq':
//        $opr = '=';
//        break;
//    case 'gt':
//        $opr = '>';
//        break;
//    case 'gte':
//        $opr = '>=';
//        break;
//    case 'lt':
//        $opr = '<';
//        break;
//    case 'lte':
//        $opr = '<=';
//        break;
//}
//if ($opr) {
//    $parameters[$parameter] = $leadSegmentFilter->getFilter();
//    $subqb->having($select.$opr.$leadSegmentFilter->getFilter());
//}
//$groupExpr->add(sprintf('%s (%s)', $operand, $subqb->getSQL()));
//break;
