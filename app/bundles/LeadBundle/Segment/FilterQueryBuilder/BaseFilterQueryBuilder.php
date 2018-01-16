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
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;

class BaseFilterQueryBuilder implements FilterQueryBuilderInterface
{
    use LeadSegmentFilterQueryBuilderTrait;

    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.basic';
    }

    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();
        $filterGlue     = $filter->getGlue();
        $filterAggr     = $filter->getAggregateFunction();

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

        // @debug we do not need this, it's just to verify we reference an existing database column
        try {
            $filter->getColumn();
        } catch (\Exception $e) {
            dump(' * IGNORED * - Unhandled field '.sprintf(' %s, operator: %s, %s', $filter->__toString(), $filter->getOperator(), print_r($filterAggr, true)));
        }

        $filterGlueFunc = $filterGlue.'Where';

        $tableAlias = $queryBuilder->getTableAlias($filter->getTable());

        // for aggregate function we need to create new alias and not reuse the old one
        if ($filterAggr) {
            $tableAlias = false;
        }

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();

            switch ($filterOperator) {
                case 'notLike':
                case 'notIn':

                case 'empty':
                case 'startsWith':
                case 'gt':
                case 'eq':
                case 'neq':
                case 'gte':
                case 'like':
                case 'lt':
                case 'lte':
                case 'in':
                    //@todo this logic needs to
                    if ($filterAggr) {
                        $queryBuilder = $queryBuilder->leftJoin(
                            $queryBuilder->getTableAlias('leads'),
                            $filter->getTable(),
                            $tableAlias,
                            sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias('leads'), $tableAlias)
                        );
                    } else {
                        $queryBuilder = $queryBuilder->innerJoin(
                            $queryBuilder->getTableAlias('leads'),
                            $filter->getTable(),
                            $tableAlias,
                            sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias('leads'), $tableAlias)
                        );
                    }
                    break;
                default:
                    //throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
                    dump('Dunno how to handle operator "'.$filterOperator.'"');
            }
        }

        switch ($filterOperator) {
            case 'empty':
                $expression = $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()),
                    $queryBuilder->expr()->eq($tableAlias.'.'.$filter->getField(), ':'.$emptyParameter = $this->generateRandomParameterName())
                );
                $queryBuilder->setParameter($emptyParameter, '');
                break;
            case 'startsWith':
            case 'endsWith':
            case 'gt':
            case 'eq':
            case 'neq':
            case 'gte':
            case 'like':
            case 'notLike':
            case 'lt':
            case 'lte':
            case 'notIn':
            case 'in':
                if ($filterAggr) {
                    $expression = $queryBuilder->expr()->$filterOperator(
                        sprintf('%s(%s)', $filterAggr, $tableAlias.'.'.$filter->getField()),
                        $filterParametersHolder
                    );
                } else {
                    $expression = $queryBuilder->expr()->$filterOperator(
                        $tableAlias.'.'.$filter->getField(),
                        $filterParametersHolder
                    );
                }
                break;
            default:
                dump(' * IGNORED * - Dunno how to handle operator "'.$filterOperator.'"');
                //throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
                $expression = '1=1';
        }

        if ($queryBuilder->isJoinTable($filter->getTable())) {
            if ($filterAggr) {
                $queryBuilder->andHaving($expression);
            } else {
                $queryBuilder->addJoinCondition($tableAlias, ' ('.$expression.')');
            }
        } else {
            $queryBuilder->$filterGlueFunc($expression);
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }

    public function aaa()
    {
        switch (true) {
            case 'tags':
            case 'globalcategory':
            case 'lead_email_received':
            case 'lead_email_sent':
            case 'device_type':
            case 'device_brand':
            case 'device_os':
                // Special handling of lead lists and tags
                $func = in_array($func, ['eq', 'in'], true) ? 'EXISTS' : 'NOT EXISTS';

                $ignoreAutoFilter = true;

                // Collect these and apply after building the query because we'll want to apply the lead first for each of the subqueries
                $subQueryFilters = [];
                switch ($leadSegmentFilter->getField()) {
                    case 'tags':
                        $table  = 'lead_tags_xref';
                        $column = 'tag_id';
                        break;
                    case 'globalcategory':
                        $table  = 'lead_categories';
                        $column = 'category_id';
                        break;
                    case 'lead_email_received':
                        $table  = 'email_stats';
                        $column = 'email_id';

                        $trueParameter                        = $this->generateRandomParameterName();
                        $subQueryFilters[$alias.'.is_read']   = $trueParameter;
                        $parameters[$trueParameter]           = true;
                        break;
                    case 'lead_email_sent':
                        $table  = 'email_stats';
                        $column = 'email_id';
                        break;
                    case 'device_type':
                        $table  = 'lead_devices';
                        $column = 'device';
                        break;
                    case 'device_brand':
                        $table  = 'lead_devices';
                        $column = 'device_brand';
                        break;
                    case 'device_os':
                        $table  = 'lead_devices';
                        $column = 'device_os_name';
                        break;
                }

                $subQb = $this->createFilterExpressionSubQuery($table, $alias, $column, $leadSegmentFilter->getFilter(), $parameters, $subQueryFilters);

                $groupExpr->add(sprintf('%s (%s)', $func, $subQb->getSQL()));
                break;
        }
    }
}
