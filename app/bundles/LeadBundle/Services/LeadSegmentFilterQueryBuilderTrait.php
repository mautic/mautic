<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 1/9/18
 * Time: 1:54 PM
 */

namespace Mautic\LeadBundle\Services;


use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Mautic\LeadBundle\Segment\LeadSegmentFilter;

trait LeadSegmentFilterQueryBuilderTrait {
    protected $parameterAliases = [];

    public function getTableAlias($tableEntity, QueryBuilder $queryBuilder) {

        $tables = $this->getTableAliases($queryBuilder);

        if (!in_array($tableEntity, $tables)) {
            var_dump(sprintf('table entity ' . $tableEntity . ' not found in "%s"', join(', ', array_keys($tables))));
        }

        return isset($tables[$tableEntity]) ? $tables[$tableEntity] : false;
    }

    public function getTableAliases(QueryBuilder $queryBuilder) {
        $queryParts = $queryBuilder->getQueryParts();
        $tables = array_reduce($queryParts['from'], function ($result, $item) {
            $result[$item['table']] = $item['alias'];
            return $result;
        }, array());

        foreach ($queryParts['join'] as $join) {
            foreach($join as $joinPart) {
                $tables[$joinPart['joinTable']] = $joinPart['joinAlias'];
            }
        }
        var_dump($tables);
        var_dump($queryParts['join']);

        return $tables;
    }

    /**
     * Generate a unique parameter name.
     *
     * @return string
     */
    protected function generateRandomParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $paramName = substr(str_shuffle($alpha_numeric), 0, 8);

        if (!in_array($paramName, $this->parameterAliases )) {
            $this->parameterAliases[] = $paramName;

            return $paramName;
        }

        return $this->generateRandomParameterName();
    }

    // should be used by filter
    protected function createJoin(QueryBuilder $queryBuilder, $target, $alias, $joinOn = '', $from = 'MauticLeadBundle:Lead') {
        $queryBuilder = $queryBuilder->leftJoin($this->getTableAlias($from, $queryBuilder), $target, $alias, sprintf(
            '%s.id = %s.lead_id' . ( $joinOn ? " and $joinOn" : ""),
            $this->getTableAlias($from, $queryBuilder),
            $alias
        ));

        return $queryBuilder;
    }

    protected function addForeignTableQuery(QueryBuilder $qb, LeadSegmentFilter $filter, $reuseTable = true) {
        $filter->createJoin($qb, $alias);
        if (isset($translated) && $translated) {
            if (isset($translated['func'])) {
                //@todo rewrite with getFullQualifiedName
                $qb->leftJoin($this->tableAliases[$translated['table']], $translated['foreign_table'], $this->tableAliases[$translated['foreign_table']], sprintf('%s.%s = %s.%s', $this->tableAliases[$translated['table']], $translated['table_field'], $this->tableAliases[$translated['foreign_table']], $translated['foreign_table_field']));

                //@todo rewrite with getFullQualifiedName
                $qb->andHaving(isset($translated['func']) ? sprintf('%s(%s.%s) %s %s', $translated['func'], $this->tableAliases[$translated['foreign_table']], $translated['field'], $filter->getSQLOperator(), $filter->getFilterConditionValue($parameterHolder)) : sprintf('%s.%s %s %s', $this->tableAliases[$translated['foreign_table']], $translated['field'], $this->getFilterOperator($filter), $this->getFilterValue($filter, $parameterHolder, $dbColumn)));

            }
            else {
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
     * @param null         $alias use alias to extend current query
     *
     * @throws \Exception
     */
    private function addForeignTableQueryWhere(QueryBuilder $qb, $filter, $alias = null) {
        dump($filter);
        if (is_array($filter)) {
            $alias = is_null($alias) ? $this->generateRandomParameterName() : $alias;
            foreach ($filter as $singleFilter) {
                $qb = $this->addForeignTableQueryWhere($qb, $singleFilter, $alias);
            }
            return $qb;
        }

        $parameterHolder = $this->generateRandomParameterName();
        $qb = $filter->createExpression($qb, $parameterHolder);

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

    protected function getJoinCondition(QueryBuilder $qb, $alias) {
        $parts = $qb->getQueryParts();
        foreach ($parts['join']['l'] as $joinedTable) {
            if ($joinedTable['joinAlias']==$alias) {
                return $joinedTable['joinCondition'];
            }
        }
        throw new \Exception(sprintf('Join alias "%s" doesn\'t exist',$alias));
    }

    protected function addJoinCondition(QueryBuilder $qb, $alias, $expr) {
        $result = $parts = $qb->getQueryPart('join');


        dump(1);
        foreach ($parts['l'] as $key=>$part) {
            dump($part);
            if ($part['joinAlias'] == $alias) {
                $result['l'][$key]['joinCondition'] = $part['joinCondition'] . " and " . $expr;
            }
        }
        dump(2);

        $qb->setQueryPart('join', $result);
        dump($qb->getQueryParts()); die();
        return $qb;
    }

    protected function replaceJoinCondition(QueryBuilder $qb, $alias, $expr) {
        $parts = $qb->getQueryPart('join');
        foreach ($parts['l'] as $key=>$part) {
            if ($part['joinAlias']==$alias) {
                $parts['l'][$key]['joinCondition'] = $expr;
            }
        }

        $qb->setQueryPart('join', $parts);
        return $qb;
    }

}