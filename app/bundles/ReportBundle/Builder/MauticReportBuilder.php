<?php

namespace Mautic\ReportBundle\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MauticReportBuilder implements ReportBuilderInterface
{
    /**
     * @var array
     */
    public const OPERATORS = [
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
    public const CHANNEL_COLUMN_CATEGORY_ID     = 'channel.category_id';

    public const CHANNEL_COLUMN_NAME            = 'channel.name';

    public const CHANNEL_COLUMN_DESCRIPTION     = 'channel.description';

    public const CHANNEL_COLUMN_DATE_ADDED      = 'channel.date_added';

    public const CHANNEL_COLUMN_CREATED_BY      = 'channel.created_by';

    public const CHANNEL_COLUMN_CREATED_BY_USER = 'channel.created_by_user';

    /**
     * @var string
     */
    private $contentTemplate;

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private Connection $db,
        private Report $entity,
        private ChannelListHelper $channelListHelper
    ) {
    }

    /**
     * @return QueryBuilder
     *
     * @throws InvalidReportQueryException
     */
    public function getQuery(array $options)
    {
        $queryBuilder = $this->configureBuilder($options);

        if (!array_key_exists('select', $queryBuilder->getQueryParts())) {
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
     * This method configures the ReportBuilder. It has to return a configured Doctrine DBAL QueryBuilder.
     *
     * @param array $options Options array
     *
     * @return QueryBuilder
     */
    private function configureBuilder(array $options)
    {
        $event = new ReportGeneratorEvent($this->entity, $options, $this->db->createQueryBuilder(), $this->channelListHelper);

        // Trigger the REPORT_ON_GENERATE event to initialize the QueryBuilder
        $this->dispatcher->dispatch($event, ReportEvents::REPORT_ON_GENERATE);

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
                                if ('notLike' === $condition) {
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
                if (isset($options['order']['column'])) {
                    $queryBuilder->orderBy($options['order']['column'], $options['order']['direction']);
                } elseif (!empty($options['order'][0][1])) {
                    [$column, $dir] = $options['order'];
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
                if (!empty($options['columns'][$o['column']]['formula'])) {
                    $queryBuilder->orderBy($options['columns'][$o['column']]['formula'], $o['direction']);
                } elseif (!empty($o['column'])) {
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
        if (!$event->getSelectColumns()) {
            $fields             = $this->entity->getColumns();
            $groupByColumns     = $queryBuilder->getQueryPart('groupBy');
            $groupByColumnsKeys = array_flip($groupByColumns);

            foreach ($fields as $field) {
                if (isset($options['columns'][$field])) {
                    $fieldOptions = $options['columns'][$field];

                    if (array_key_exists('channelData', $fieldOptions)) {
                        $selectText = $this->buildCaseSelect($fieldOptions['channelData']);
                    } else {
                        // If there is a group by, and this field has groupByFormula
                        if (isset($fieldOptions['groupByFormula']) && isset($groupByColumnsKeys[$fieldOptions['groupByFormula']])) {
                            $selectText = $fieldOptions['groupByFormula'];
                        } elseif (isset($fieldOptions['formula'])) {
                            $selectText = $fieldOptions['formula'];
                        } else {
                            $selectText = $this->sanitizeColumnName($field);
                        }
                    }

                    // support for prefix and suffix to value in query
                    $prefix     = $fieldOptions['prefix'] ?? '';
                    $suffix     = $fieldOptions['suffix'] ?? '';
                    if ($prefix || $suffix) {
                        $selectText = 'CONCAT(\''.$prefix.'\', '.$selectText.',\''.$suffix.'\')';
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
        array_walk($selectColumns, function (&$columnValue, $columnIndex) use ($countSql): void {
            if (str_contains($columnValue, '{{count}}')) {
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
     */
    private function buildCaseSelect(array $channelData): string
    {
        $case = 'CASE';

        foreach ($channelData as $data) {
            $case .= ' WHEN '.$data['column'].' IS NOT NULL THEN '.$data['column'];
        }

        return $case.' ELSE NULL END ';
    }

    private function applyFilters(array $filters, QueryBuilder $queryBuilder, array $filterDefinitions): void
    {
        $expr     = $queryBuilder->expr();
        $orGroups = [];
        $andGroup = [];

        if (count($filters)) {
            foreach ($filters as $i => $filter) {
                $exprFunction = $filter['expr'] ?? $filter['condition'];
                $paramName    = sprintf('i%dc%s', $i, InputHelper::alphanum($filter['column']));

                if (array_key_exists('glue', $filter) && 'or' === $filter['glue']) {
                    if ($andGroup) {
                        $orGroups[] = CompositeExpression::and(...$andGroup);
                        $andGroup   = [];
                    }
                }

                $tagCondition = $this->getTagCondition($filter);
                if ($tagCondition) {
                    $andGroup[] = $tagCondition;
                    continue;
                }

                switch ($exprFunction) {
                    case 'notEmpty':
                        $andGroup[] = $expr->isNotNull($filter['column']);
                        if ($this->doesColumnSupportEmptyValue($filter, $filterDefinitions)) {
                            $andGroup[] = $expr->neq($filter['column'], $expr->literal(''));
                        }
                        break;
                    case 'empty':
                        $expression = $queryBuilder->expr()->or(
                            $queryBuilder->expr()->isNull($filter['column'])
                        );
                        if ($this->doesColumnSupportEmptyValue($filter, $filterDefinitions)) {
                            $expression = $expression->with(
                                $queryBuilder->expr()->eq($filter['column'], $expr->literal(''))
                            );
                        }

                        $andGroup[] = $expression;
                        break;
                    case 'neq':
                        $columnValue = ":$paramName";
                        $expression  = $queryBuilder->expr()->or(
                            $queryBuilder->expr()->isNull($filter['column']),
                            $queryBuilder->expr()->$exprFunction($filter['column'], $columnValue)
                        );
                        $queryBuilder->setParameter($paramName, $filter['value']);
                        $andGroup[] = $expression;
                        break;
                    default:
                        if ('' == trim($filter['value'])) {
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
                                    break 2;
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
                            case 'url':
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
                        $andGroup[] = $expr->{$exprFunction}($filter['column'], $columnValue);
                }
            }
        }

        if ($orGroups) {
            // Add the remaining $andGroup to the rest of the $orGroups if exists so we don't miss it.
            $orGroups[] = CompositeExpression::and(...$andGroup);
            $queryBuilder->andWhere(CompositeExpression::or(...$orGroups));
        } else {
            $queryBuilder->andWhere(CompositeExpression::and(...$andGroup));
        }
    }

    /**
     * @param array<string, mixed> $filter
     */
    public function getTagCondition(array $filter): ?string
    {
        if ('tag' !== $filter['column']) {
            return null;
        }

        $tagSubQuery = $this->db->createQueryBuilder();
        $tagSubQuery->select('DISTINCT lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'ltx');

        if (in_array($filter['condition'], ['in', 'notIn']) && !empty($filter['value'])) {
            $tagSubQuery->where($tagSubQuery->expr()->in('ltx.tag_id', $filter['value']));
        }

        if (in_array($filter['condition'], ['in', 'notEmpty'])) {
            return $tagSubQuery->expr()->in('l.id', $tagSubQuery->getSQL());
        } elseif (in_array($filter['condition'], ['notIn', 'empty'])) {
            return $tagSubQuery->expr()->notIn('l.id', $tagSubQuery->getSQL());
        }

        return null;
    }

    /**
     * We must sanitize the table aliases as they might be auto generated.
     * Aliases like "8e296a06" makes MySql to think it is a number.
     * Expects param in format "table_alias.column_name".
     */
    private function sanitizeColumnName(string $fullColumnName): string
    {
        [$tableAlias, $columnName] = explode('.', $fullColumnName);

        return "`{$tableAlias}`.`{$columnName}`";
    }

    /**
     * @param mixed[] $filter
     * @param mixed[] $filterDefinitions
     */
    private function doesColumnSupportEmptyValue(array $filter, array $filterDefinitions): bool
    {
        $type = $filterDefinitions[$filter['column']]['type'] ?? null;

        return !in_array($type, ['date', 'datetime'], true);
    }
}
