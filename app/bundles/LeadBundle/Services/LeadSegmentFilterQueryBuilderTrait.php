<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 1/9/18
 * Time: 1:54 PM.
 */

namespace Mautic\LeadBundle\Services;

use Mautic\LeadBundle\Segment\Query\QueryBuilder;

trait LeadSegmentFilterQueryBuilderTrait
{
    // @todo make static to asure single instance
    protected $parameterAliases = [];

    /**
     * Generate a unique parameter name.
     *
     * @return string
     */
    protected function generateRandomParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $paramName = substr(str_shuffle($alpha_numeric), 0, 8);

        if (!in_array($paramName, $this->parameterAliases)) {
            $this->parameterAliases[] = $paramName;

            return $paramName;
        }

        return $this->generateRandomParameterName();
    }

    public function addNewLeadsRestrictions(QueryBuilder $queryBuilder, $leadListId, $whatever)
    {
        $queryBuilder->select('max(l.id) maxId, count(l.id) as leadCount');
        $queryBuilder->addGroupBy('l.id');

        $parts     = $queryBuilder->getQueryParts();
        $setHaving =  (count($parts['groupBy']) || !is_null($parts['having']));

        $tableAlias = $this->generateRandomParameterName();
        $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias, $tableAlias.'.lead_id = l.id');
        $queryBuilder->addSelect($tableAlias.'.lead_id');

        $expression = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq($tableAlias.'.leadlist_id', $leadListId),
            $queryBuilder->expr()->lte($tableAlias.'.date_added', "'".$whatever['dateTime']."'")
        );

        $restrictionExpression = $queryBuilder->expr()->isNull($tableAlias.'.lead_id');

        $queryBuilder->addJoinCondition($tableAlias, $expression);

        if ($setHaving) {
            $queryBuilder->andHaving($restrictionExpression);
        } else {
            $queryBuilder->andWhere($restrictionExpression);
        }

        return $queryBuilder;
    }
}
