<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 1/9/18
 * Time: 1:54 PM.
 */

namespace Mautic\LeadBundle\Services;

use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

trait LeadSegmentFilterQueryBuilderTrait
{
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

    // should be used by filter
    protected function createJoin(QueryBuilder $queryBuilder, $target, $alias, $joinOn = '', $from = 'MauticLeadBundle:Lead')
    {
        $queryBuilder = $queryBuilder->leftJoin($this->getTableAlias($from, $queryBuilder), $target, $alias, sprintf(
            '%s.id = %s.lead_id'.($joinOn ? " and $joinOn" : ''),
            $this->getTableAlias($from, $queryBuilder),
            $alias
        ));

        return $queryBuilder;
    }

    protected function addForeignTableQuery(QueryBuilder $qb, LeadSegmentFilter $filter)
    {
        $filter->createJoin($qb, $alias);
        if (isset($translated) && $translated) {
            if (isset($translated['func'])) {
                //@todo rewrite with getFullQualifiedName
                $qb->leftJoin($this->tableAliases[$translated['table']], $translated['foreign_table'], $this->tableAliases[$translated['foreign_table']], sprintf('%s.%s = %s.%s', $this->tableAliases[$translated['table']], $translated['table_field'], $this->tableAliases[$translated['foreign_table']], $translated['foreign_table_field']));

                //@todo rewrite with getFullQualifiedName
                $qb->andHaving(isset($translated['func']) ? sprintf('%s(%s.%s) %s %s', $translated['func'], $this->tableAliases[$translated['foreign_table']], $translated['field'], $filter->getSQLOperator(), $filter->getFilterConditionValue($parameterHolder)) : sprintf('%s.%s %s %s', $this->tableAliases[$translated['foreign_table']], $translated['field'], $this->getFilterOperator($filter), $this->getFilterValue($filter, $parameterHolder, $dbColumn)));
            } else {
                //@todo rewrite with getFullQualifiedName
                $qb->innerJoin($this->tableAliases[$translated['table']], $translated['foreign_table'], $this->tableAliases[$translated['foreign_table']], sprintf('%s.%s = %s.%s and %s', $this->tableAliases[$translated['table']], $translated['table_field'], $this->tableAliases[$translated['foreign_table']], $translated['foreign_table_field'], sprintf('%s.%s %s %s', $this->tableAliases[$translated['foreign_table']], $translated['field'], $filter->getSQLOperator(), $filter->getFilterConditionValue($parameterHolder))));
            }

            $qb->setParameter($parameterHolder, $filter->getFilter());

            $qb->groupBy(sprintf('%s.%s', $this->tableAliases[$translated['table']], $translated['table_field']));
        } else {
            //  Default behaviour, translation not necessary
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param              $filter
     * @param null         $alias  use alias to extend current query
     *
     * @throws \Exception
     */
    private function addForeignTableQueryWhere(QueryBuilder $qb, $filter, $alias = null)
    {
        dump($filter);
        if (is_array($filter)) {
            $alias = is_null($alias) ? $this->generateRandomParameterName() : $alias;
            foreach ($filter as $singleFilter) {
                $qb = $this->addForeignTableQueryWhere($qb, $singleFilter, $alias);
            }

            return $qb;
        }

        $parameterHolder = $this->generateRandomParameterName();
        $qb              = $filter->createExpression($qb, $parameterHolder);

        return $qb;
        dump($expr);
        die();

        //$qb = $qb->andWhere($expr);
        $qb->setParameter($parameterHolder, $filter->getFilter());

        //var_dump($qb->getSQL()); die();

//        die();
//
//        if (isset($translated) && $translated) {
//            if (isset($translated['func'])) {
//                //@todo rewrite with getFullQualifiedName
//                $qb->leftJoin($this->getTableAlias($filter->get), $translated['foreign_table'], $this->tableAliases[$translated['foreign_table']], sprintf('%s.%s = %s.%s', $this->tableAliases[$translated['table']], $translated['table_field'], $this->tableAliases[$translated['foreign_table']], $translated['foreign_table_field']));
//
//                //@todo rewrite with getFullQualifiedName
//                $qb->andHaving(isset($translated['func']) ? sprintf('%s(%s.%s) %s %s', $translated['func'], $this->tableAliases[$translated['foreign_table']], $translated['field'], $filter->getSQLOperator(), $filter->getFilterConditionValue($parameterHolder)) : sprintf('%s.%s %s %s', $this->tableAliases[$translated['foreign_table']], $translated['field'], $this->getFilterOperator($filter), $this->getFilterValue($filter, $parameterHolder, $dbColumn)));
//
//            }
//            else {
//                //@todo rewrite with getFullQualifiedName
//                $qb->innerJoin($this->tableAliases[$translated['table']], $translated['foreign_table'], $this->tableAliases[$translated['foreign_table']], sprintf('%s.%s = %s.%s and %s', $this->tableAliases[$translated['table']], $translated['table_field'], $this->tableAliases[$translated['foreign_table']], $translated['foreign_table_field'], sprintf('%s.%s %s %s', $this->tableAliases[$translated['foreign_table']], $translated['field'], $filter->getSQLOperator(), $filter->getFilterConditionValue($parameterHolder))));
//            }
//
//
//            $qb->setParameter($parameterHolder, $filter->getFilter());
//
//            $qb->groupBy(sprintf('%s.%s', $this->tableAliases[$translated['table']], $translated['table_field']));
//        }
    }
}
