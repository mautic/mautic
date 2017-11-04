<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Mautic Report Builder class.
 */
final class MauticReportBuilder implements ReportBuilderInterface
{
    /**
     * @var array
     */
    const OPERATORS = [
        'default' => [
            'eq'         => 'mautic.core.operator.equals',
            'gt'         => 'mautic.core.operator.greaterthan',
            'gte'        => 'mautic.core.operator.greaterthanequals',
            'lt'         => 'mautic.core.operator.lessthan',
            'lte'        => 'mautic.core.operator.lessthanequals',
            'neq'        => 'mautic.core.operator.notequals',
            'like'       => 'mautic.core.operator.islike',
            'notLike'    => 'mautic.core.operator.isnotlike',
            'empty'      => 'mautic.core.operator.isempty',
            'notEmpty'   => 'mautic.core.operator.isnotempty',
            'contains'   => 'mautic.core.operator.contains',
            'startsWith' => 'mautic.core.operator.starts.with',
            'endsWith'   => 'mautic.core.operator.ends.with',
        ],
        'bool' => [
            'eq'  => 'mautic.core.operator.equals',
            'neq' => 'mautic.core.operator.notequals',
        ],
        'int' => [
            'eq'  => 'mautic.core.operator.equals',
            'gt'  => 'mautic.core.operator.greaterthan',
            'gte' => 'mautic.core.operator.greaterthanequals',
            'lt'  => 'mautic.core.operator.lessthan',
            'lte' => 'mautic.core.operator.lessthanequals',
            'neq' => 'mautic.core.operator.notequals',
        ],
        'multiselect' => [
            'in'    => 'mautic.core.operator.in',
            'notIn' => 'mautic.core.operator.notin',
        ],
        'select' => [
            'eq'  => 'mautic.core.operator.equals',
            'neq' => 'mautic.core.operator.notequals',
        ],
        'text' => [
            'eq'         => 'mautic.core.operator.equals',
            'neq'        => 'mautic.core.operator.notequals',
            'empty'      => 'mautic.core.operator.isempty',
            'notEmpty'   => 'mautic.core.operator.isnotempty',
            'like'       => 'mautic.core.operator.islike',
            'notLike'    => 'mautic.core.operator.isnotlike',
            'contains'   => 'mautic.core.operator.contains',
            'startsWith' => 'mautic.core.operator.starts.with',
            'endsWith'   => 'mautic.core.operator.ends.with',
        ],
    ];

    /**
     * Standard Channel Columns.
     */
    const CHANNEL_COLUMN_CATEGORY_ID     = 'channel.category_id';
    const CHANNEL_COLUMN_NAME            = 'channel.name';
    const CHANNEL_COLUMN_DESCRIPTION     = 'channel.description';
    const CHANNEL_COLUMN_DATE_ADDED      = 'channel.date_added';
    const CHANNEL_COLUMN_CREATED_BY      = 'channel.created_by';
    const CHANNEL_COLUMN_CREATED_BY_USER = 'channel.created_by_user';

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var \Mautic\ReportBundle\Entity\Report
     */
    private $entity;

    /**
     * @var string
     */
    private $contentTemplate;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var ChannelListHelper
     */
    private $channelListHelper;

    /**
     * MauticReportBuilder constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param Connection               $db
     * @param Report                   $entity
     * @param ChannelListHelper        $channelListHelper
     */
    public function __construct(EventDispatcherInterface $dispatcher, Connection $db, Report $entity, ChannelListHelper $channelListHelper)
    {
        $this->entity            = $entity;
        $this->dispatcher        = $dispatcher;
        $this->db                = $db;
        $this->channelListHelper = $channelListHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidReportQueryException
     */
    public function getQuery(array $options)
    {
        $queryBuilder = $this->configureBuilder($options);

        if ($queryBuilder->getType() !== QueryBuilder::SELECT) {
            throw new InvalidReportQueryException('Only SELECT statements are valid');
        }

        return $queryBuilder;
    }

    /**
     * Gets the getContentTemplate path.
     *
     * @return string
     */
    public function getContentTemplate()
    {
        return $this->contentTemplate;
    }

    /**
     * Configures builder.
     *
     * This method configures the ReportBuilder. It has to return a configured Doctrine DBAL QueryBuilder.
     *
     * @param array $options Options array
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function configureBuilder(array $options)
    {
        // Trigger the REPORT_ON_GENERATE event to initialize the QueryBuilder
        /** @var ReportGeneratorEvent $event */
        $event = $this->dispatcher->dispatch(
            ReportEvents::REPORT_ON_GENERATE,
            new ReportGeneratorEvent($this->entity, $options, $this->db->createQueryBuilder(), $this->channelListHelper)
        );

        // Build the QUERY
        $queryBuilder = $event->getQueryBuilder();

        // Set Content Template
        $this->contentTemplate = $event->getContentTemplate();
        $standardFilters       = $this->entity->getFilters();

        // Setup filters
        if (isset($options['dynamicFilters'])) {
            $dynamicFilters = $options['dynamicFilters'];

            foreach ($dynamicFilters as $key => $dynamicFilter) {
                foreach ($standardFilters as $i => $filter) {
                    if ($filter['column'] === $key && $filter['dynamic']) {
                        $value     = $dynamicFilter['value'];
                        $condition = $filter['condition'];

                        switch ($condition) {
                            case 'startsWith':
                                $value = $value.'%';
                                break;
                            case 'endsWith':
                                $value = '%'.$value;
                                break;
                            case 'like':
                            case 'notLike':
                            case 'contains':
                                if ($condition === 'notLike') {
                                    $dynamicFilter['expr'] = 'notLike';
                                }

                                $value = '%'.$value.'%';
                                break;
                        }

                        $dynamicFilter['value'] = $value;

                        // Overwrite the standard filter with the dynamic
                        $standardFilters[$i] = array_merge($filter, $dynamicFilter);
                    }
                }
            }
        }

        // Build WHERE clause
        if (!empty($standardFilters)) {
            if (!$filterExpr = $event->getFilterExpression()) {
                $this->applyFilters($standardFilters, $queryBuilder, $options['filters']);
            } else {
                $queryBuilder->andWhere($filterExpr);
            }
        }

        // Build ORDER BY clause
        if (!empty($options['order'])) {
            if (is_array($options['order'])) {
                if (isset($o['column'])) {
                    $queryBuilder->orderBy($options['order']['column'], $options['order']['direction']);
                } elseif (!empty($options['order'][0][1])) {
                    list($column, $dir) = $options['order'];
                    $queryBuilder->orderBy($column, $dir);
                } else {
                    foreach ($options['order'] as $order) {
                        $queryBuilder->orderBy($order);
                    }
                }
            } else {
                $queryBuilder->orderBy($options['order']);
            }
        } elseif ($order = $this->entity->getTableOrder()) {
            foreach ($order as $o) {
                if (!empty($o['column'])) {
                    $queryBuilder->orderBy($o['column'], $o['direction']);
                }
            }
        }

        // Build GROUP BY
        if ($groupByOptions = $this->entity->getGroupBy()) {
            $groupByColumns = [];

            foreach ($groupByOptions as $groupBy) {
                if (isset($options['columns'][$groupBy])) {
                    $fieldOptions = $options['columns'][$groupBy];

                    if (isset($fieldOptions['groupByFormula'])) {
                        $groupByColumns[] = $fieldOptions['groupByFormula'];
                    } elseif (isset($fieldOptions['formula'])) {
                        $groupByColumns[] = $fieldOptions['formula'];
                    } else {
                        $groupByColumns[] = $groupBy;
                    }
                }
            }

            $queryBuilder->addGroupBy($groupByColumns);
        } elseif (!empty($options['groupby']) && empty($groupByOptions)) {
            $queryBuilder->addGroupBy($options['groupby']);
        }

        // Build LIMIT clause
        if (!empty($options['limit'])) {
            $queryBuilder->setFirstResult($options['start'])
                ->setMaxResults($options['limit']);
        }

        if (!empty($options['having'])) {
            if (is_array($options['having'])) {
                foreach ($options['having'] as $having) {
                    $queryBuilder->andHaving($having);
                }
            } else {
                $queryBuilder->having($options['having']);
            }
        }

        $selectColumns = [];

        // Build SELECT clause
        if (!$selectOptions = $event->getSelectColumns()) {
            $fields         = $this->entity->getColumns();
            $groupByColumns = $queryBuilder->getQueryPart('groupBy');

            foreach ($fields as $field) {
                if (isset($options['columns'][$field])) {
                    $fieldOptions = $options['columns'][$field];

                    if (array_key_exists('channelData', $fieldOptions)) {
                        $selectText = $this->buildCaseSelect($fieldOptions['channelData']);
                    } else {
                        // If there is a group by, and this field has groupByFormula
                        if (isset($groupByColumns) && isset($fieldOptions['groupByFormula'])) {
                            $selectText = $fieldOptions['groupByFormula'];
                        } elseif (isset($fieldOptions['formula'])) {
                            $selectText = $fieldOptions['formula'];
                        } else {
                            $selectText = $field;
                        }
                    }

                    if (isset($fieldOptions['alias'])) {
                        $selectText .= ' AS '.$fieldOptions['alias'];
                    }

                    $selectColumns[] = $selectText;
                }
            }
        }

        // Generate a count query in case a formula needs total number
        $countQuery = clone $queryBuilder;
        $countQuery->select('COUNT(*) as count');

        $countSql = sprintf('(%s)', $countQuery->getSQL());

        // Replace {{count}} with the count query
        array_walk($selectColumns, function (&$columnValue, $columnIndex) use ($countSql) {
            if (strpos($columnValue, '{{count}}') !== false) {
                $columnValue = str_replace('{{count}}', $countSql, $columnValue);
            }
        });

        $queryBuilder->addSelect($selectColumns);

        // Add Aggregators
        $aggregators      = $this->entity->getAggregators();
        $aggregatorSelect = [];

        if ($aggregators && $groupByOptions) {
            foreach ($aggregators as $aggregator) {
                if (isset($options['columns'][$aggregator['column']]) && isset($options['columns'][$aggregator['column']]['formula'])) {
                    $columnSelect = $options['columns'][$aggregator['column']]['formula'];
                } else {
                    $columnSelect = $aggregator['column'];
                }

                $selectText = sprintf('%s(%s)', $aggregator['function'], $columnSelect);

                if ($aggregator['function'] === 'AVG') {
                    $selectText = sprintf('ROUND(%s)', $selectText);
                }

                $aggregatorSelect[] = sprintf("%s AS '%s %s'", $selectText, $aggregator['function'], $aggregator['column']);
            }

            $queryBuilder->addSelect($aggregatorSelect);
        }

        return $queryBuilder;
    }

    /**
     * Build a CASE select statement.
     *
     * @param array $channelData ['channelName' => ['prefix' => XX, 'column' => 'XX.XX']
     *
     * @return string
     */
    private function buildCaseSelect(array $channelData)
    {
        $case = 'CASE';

        foreach ($channelData as $channel => $data) {
            $case .= ' WHEN '.$data['column'].' IS NOT NULL THEN '.$data['column'];
        }

        $case .= ' ELSE NULL END ';

        return $case;
    }

    /**
     * @param array        $filters
     * @param QueryBuilder $queryBuilder
     * @param array        $filterDefinitions
     *
     * @return bool
     */
    private function applyFilters(array $filters, QueryBuilder $queryBuilder, array $filterDefinitions)
    {
        $expr      = $queryBuilder->expr();
        $groups    = [];
        $groupExpr = $queryBuilder->expr()->andX();

        if (count($filters)) {
            foreach ($filters as $i => $filter) {
                $exprFunction = isset($filter['expr']) ? $filter['expr'] : $filter['condition'];
                $paramName    = sprintf('i%dc%s', $i, InputHelper::alphanum($filter['column']));

                if (array_key_exists('glue', $filter) && $filter['glue'] === 'or') {
                    if ($groupExpr->count()) {
                        $groups[]  = $groupExpr;
                        $groupExpr = $queryBuilder->expr()->andX();
                    }
                }

                switch ($exprFunction) {
                    case 'notEmpty':
                        $groupExpr->add(
                            $expr->isNotNull($filter['column'])
                        );
                        $groupExpr->add(
                            $expr->neq($filter['column'], $expr->literal(''))
                        );
                        break;
                    case 'empty':
                        $groupExpr->add(
                            $expr->isNull($filter['column'])
                        );
                        $groupExpr->add(
                            $expr->eq($filter['column'], $expr->literal(''))
                        );
                        break;
                    default:
                        if (trim($filter['value']) == '') {
                            // Ignore empty
                            break;
                        }

                        $columnValue = ":$paramName";
                        $type        = $filterDefinitions[$filter['column']]['type'];
                        if (isset($filterDefinitions[$filter['column']]['formula'])) {
                            $filter['column'] = $filterDefinitions[$filter['column']]['formula'];
                        }

                        switch ($type) {
                            case 'bool':
                            case 'boolean':
                                if ((int) $filter['value'] > 1) {
                                    // Ignore the "reset" value of "2"
                                    continue 2;
                                }

                                $queryBuilder->setParameter($paramName, $filter['value'], 'boolean');
                                break;

                            case 'float':
                                $columnValue = (float) $filter['value'];
                                break;

                            case 'int':
                            case 'integer':
                                $columnValue = (int) $filter['value'];
                                break;

                            case 'string':
                            case 'email':
                                switch ($exprFunction) {
                                    case 'startsWith':
                                        $exprFunction    = 'like';
                                        $filter['value'] = $filter['value'].'%';
                                        break;
                                    case 'endsWith':
                                        $exprFunction    = 'like';
                                        $filter['value'] = '%'.$filter['value'];
                                        break;
                                    case 'contains':
                                        $exprFunction    = 'like';
                                        $filter['value'] = '%'.$filter['value'].'%';
                                        break;
                                }

                                $queryBuilder->setParameter($paramName, $filter['value']);
                                break;

                            default:
                                $queryBuilder->setParameter($paramName, $filter['value']);
                        }

                        $groupExpr->add(
                            $expr->{$exprFunction}($filter['column'], $columnValue)
                        );
                }
            }
        }

        // Get the last of the filters
        if ($groupExpr->count()) {
            $groups[] = $groupExpr;
        }

        if (count($groups) === 1) {
            // Only one andX expression
            $filterExpr = $groups[0];
        } elseif (count($groups) > 1) {
            // Sets of expressions grouped by OR
            $orX = $queryBuilder->expr()->orX();
            $orX->addMultiple($groups);

            // Wrap in a andX for other functions to append
            $filterExpr = $queryBuilder->expr()->andX($orX);
        } else {
            $filterExpr = $groupExpr;
        }

        if ($filterExpr->count()) {
            $queryBuilder->andWhere($filterExpr);

            return true;
        }

        return false;
    }
}
