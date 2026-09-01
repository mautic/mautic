<?php

namespace Mautic\ReportBundle\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
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
     * SQL tokens that may appear inside report expressions but are never column
     * references; used by extractBaseColumns(). Function names are already
     * excluded by the not-followed-by-parenthesis check.
     *
     * Includes both MySQL and PostgreSQL tokens so the same builder works on
     * either platform without treating keywords as column names.
     */
    private const SQL_KEYWORD_TOKENS = [
        // Common
        'SELECT', 'FROM', 'WHERE', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END', 'AND',
        'OR', 'NOT', 'NULL', 'IS', 'IN', 'LIKE', 'BETWEEN', 'EXISTS', 'DISTINCT',
        'AS', 'ASC', 'DESC', 'INTERVAL', 'DAY', 'DAYOFWEEK', 'DAYOFMONTH',
        'DAYOFYEAR', 'HOUR', 'MINUTE', 'SECOND', 'WEEK', 'MONTH', 'QUARTER',
        'YEAR', 'TRUE', 'FALSE',
        // PostgreSQL
        'ILIKE', 'SIMILAR', 'OVERLAPS', 'EXTRACT', 'FILTER', 'WITHIN',
        'GROUPING', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP',
        'LOCALTIME', 'LOCALTIMESTAMP', 'AT', 'TIME', 'ZONE', 'EPOCH',
        'CENTURY', 'DECADE', 'DOW', 'DOY', 'ISODOW', 'ISOYEAR', 'MICROSECONDS',
        'MILLISECONDS', 'MILLENNIUM', 'TIMEZONE', 'TIMEZONE_HOUR', 'TIMEZONE_MINUTE',
    ];

    /**
     * Aggregate functions whose arguments never need to appear in GROUP BY.
     *
     * Covers MySQL and PostgreSQL names used in report formulas.
     */
    private const AGGREGATE_FUNCTIONS = [
        // Common / MySQL
        'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'GROUP_CONCAT', 'BIT_AND',
        'BIT_OR', 'BIT_XOR', 'JSON_ARRAYAGG', 'JSON_OBJECTAGG', 'STD',
        'STDDEV', 'VARIANCE',
        // PostgreSQL
        'STRING_AGG', 'ARRAY_AGG', 'BOOL_AND', 'BOOL_OR', 'EVERY',
        'JSON_AGG', 'JSONB_AGG', 'JSON_OBJECT_AGG', 'JSONB_OBJECT_AGG',
        'XMLAGG', 'STDDEV_POP', 'STDDEV_SAMP', 'VAR_POP', 'VAR_SAMP',
        'MODE', 'PERCENTILE_CONT', 'PERCENTILE_DISC',
    ];

    private ?string $contentTemplate = null;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly Connection $db,
        private readonly Report $entity,
        private readonly ChannelListHelper $channelListHelper,
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
     */
    public function getContentTemplate(): ?string
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

        // Get Platform
        $platform = $this->db->getDatabasePlatform();

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
                                $value .= '%';
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
        $orderByColumns = [];
        if (!empty($options['order'])) {
            if (is_array($options['order'])) {
                if (isset($options['order']['column'])) {
                    $queryBuilder->orderBy($options['order']['column'], $options['order']['direction']);
                    $orderByColumns[] = $options['order']['column'];
                } elseif (!empty($options['order'][0][1])) {
                    [$column, $dir] = $options['order'];
                    $queryBuilder->orderBy($column, $dir);
                    $orderByColumns[] = $column;
                } else {
                    foreach ($options['order'] as $order) {
                        $queryBuilder->orderBy($order);

                        if (is_string($order)) {
                            $orderByColumns[] = $order;
                        }
                    }
                }
            } else {
                $queryBuilder->orderBy($options['order']);
                $orderByColumns[] = $options['order'];
            }
        } elseif ($order = $this->entity->getTableOrder()) {
            foreach ($order as $o) {
                if (!empty($options['columns'][$o['column']]['formula'])) {
                    $queryBuilder->orderBy($options['columns'][$o['column']]['formula'], $o['direction']);
                    $orderByColumns[] = $options['columns'][$o['column']]['formula'];
                } elseif (!empty($o['column'])) {
                    $queryBuilder->orderBy($o['column'], $o['direction']);
                    $orderByColumns[] = $o['column'];
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

        $selectColumns            = [];
        $aggregators              = $this->entity->getAggregators();
        $groupByColumns           = $queryBuilder->getQueryPart('groupBy') ?? [];
        $groupByColumnsKeys       = array_flip($groupByColumns);
        $aggregatorFieldKeys      = $groupByOptions && $aggregators
            ? array_flip(array_column($aggregators, 'column'))
            : [];
        $ungroupedSelectColumns   = [];
        $expressionSelectColumns  = [];

        // Build SELECT clause
        if (!$event->getSelectColumns()) {
            $fields           = $this->entity->getColumns();
            $groupByFieldKeys = $groupByOptions ? array_flip($groupByOptions) : [];

            foreach ($fields as $field) {
                // With GROUP BY + aggregators, a column listed only for COUNT/AVG must not
                // also appear as a raw SELECT (ONLY_FULL_GROUP_BY rejects ph.id when grouped
                // by ph.url). Columns not in groupBy but functionally dependent (e.g. e.subject
                // when grouped by e.id) must remain in SELECT.
                if ($groupByOptions && !isset($groupByFieldKeys[$field]) && isset($aggregatorFieldKeys[$field])) {
                    continue;
                }

                if (isset($options['columns'][$field])) {
                    $fieldOptions = $options['columns'][$field];

                    if (array_key_exists('channelData', $fieldOptions)) {
                        $selectText = $this->buildCaseSelect($fieldOptions['channelData']);
                        $expressionSelectColumns[] = $selectText;
                    } else {
                        // If there is a group by, and this field has groupByFormula
                        if (isset($fieldOptions['groupByFormula']) && isset($groupByColumnsKeys[$fieldOptions['groupByFormula']])) {
                            $selectText = $fieldOptions['groupByFormula'];
                        } elseif (isset($fieldOptions['formula'])) {
                            $selectText = $fieldOptions['formula'];
                            // The expression itself is not a groupable column; the
                            // completion pass below groups its base columns instead.
                            $expressionSelectColumns[] = $selectText;
                        } else {
                            $selectText               = DatabasePlatform::quoteColumn($platform, $field);
                            $ungroupedSelectColumns[] = $field;
                        }
                    }

                    // support for prefix and suffix to value in query
                    $prefix = $fieldOptions['prefix'] ?? '';
                    $suffix = $fieldOptions['suffix'] ?? '';
                    if ($prefix || $suffix) {
                        $selectText = 'CONCAT(\''.$prefix.'\', '.$selectText.',\''.$suffix.'\')';
                    }

                    if (isset($fieldOptions['alias'])) {
                        // Match upstream: do not quote the alias. Quoted aliases
                        // change result-column keys on some drivers and break
                        // report table mapping (Focus functional tests).
                        $selectText .= ' AS '.$fieldOptions['alias'];
                    }

                    $selectColumns[] = $selectText;
                }
            }
        }

        // Complete GROUP BY with every non-aggregated column that ends up in SELECT or
        // ORDER BY, so grouped reports stay valid under ONLY_FULL_GROUP_BY (and on
        // engines without functional-dependency detection) without silently dropping
        // columns the user asked for. Aggregator targets are deliberately excluded:
        // they appear in SELECT only as the aggregate expression. The GROUP BY part is
        // rebuilt from a normalized, deduplicated list so columns pre-registered by a
        // listener are merged in rather than appended a second time.
        if ($groupByColumns) {
            $normalizedAggregatorKeys = array_flip(array_map(
                $this->normalizeColumnIdentifier(...),
                array_keys($aggregatorFieldKeys)
            ));
            $normalizedGroupBy = [];
            $dedupedGroupBy    = [];

            // Existing part entries (options, listeners) are already valid GROUP BY
            // expressions; deduplicate them as-is. Only the new completion candidates
            // go through base-column extraction.
            foreach ($groupByColumns as $column) {
                $normalized = $this->normalizeColumnIdentifier($column);

                if ('' === $normalized || in_array($normalized, $normalizedGroupBy, true)) {
                    continue;
                }

                $normalizedGroupBy[] = $normalized;
                $dedupedGroupBy[]    = $column;
            }

            foreach ([...$ungroupedSelectColumns, ...$expressionSelectColumns, ...$orderByColumns] as $column) {
                if (str_contains($column, '{{count}}')) {
                    continue;
                }

                foreach ($this->extractBaseColumns($column) as $baseColumn) {
                    $normalized = $this->normalizeColumnIdentifier($baseColumn);

                    if ('' === $normalized
                        || in_array($normalized, $normalizedGroupBy, true)
                        || isset($normalizedAggregatorKeys[$normalized])
                    ) {
                        continue;
                    }

                    $normalizedGroupBy[] = $normalized;
                    $dedupedGroupBy[] = $baseColumn;
                }
            }

            if ($dedupedGroupBy !== $groupByColumns) {
                $queryBuilder->resetQueryPart('groupBy');
                $queryBuilder->addGroupBy(...$dedupedGroupBy);
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
        $aggregatorSelect = [];

        if ($aggregators && $groupByOptions) {
            foreach ($aggregators as $aggregator) {
                if (isset($options['columns'][$aggregator['column']]) && isset($options['columns'][$aggregator['column']]['formula'])) {
                    $columnSelect = $options['columns'][$aggregator['column']]['formula'];
                } else {
                    $columnSelect = $aggregator['column'];
                }

                $selectText = DatabasePlatform::getAggregatorExpression(
                    $this->db->getDatabasePlatform(),
                    $aggregator['function'],
                    $columnSelect
                );

                $alias               = sprintf('%s %s', $aggregator['function'], $aggregator['column']);
                $quotedAlias         = DatabasePlatform::quoteIdentifier($platform, $alias);
                $aggregatorSelect[]  = sprintf('%s AS %s', $selectText, $quotedAlias);
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

    /**
     * Returns the outer-level column references contained in a SELECT/ORDER BY
     * expression (e.g. ph.date_hit inside DATE(ph.date_hit)), so the GROUP BY
     * completion can group them individually instead of appending the whole
     * expression as an opaque string.
     *
     * String literals, aggregate-function arguments and subquery contents are
     * skipped: those identifiers are aggregated or belong to another query scope
     * and must never be pulled into the outer GROUP BY.
     *
     * @return string[]
     */
    private function extractBaseColumns(string $expression): array
    {
        // Strip string literals so their contents are never scanned. MySQL
        // treats both single- and double-quoted strings as literals (FocusBundle
        // and several channel subscribers write conditions like fs.type = "view").
        // A doubled-quote alternative in the pattern would let one match span
        // from a literal's closing quote to the next literal's opening quote,
        // swallowing everything between them (including subquery parens), so
        // escapes are handled by the \\. branch instead.
        $expression = preg_replace("/'(?:\\\\.|[^'\\\\])*'/", "''", $expression) ?? '';
        $expression = preg_replace('/"(?:\\\\.|[^"\\\\])*"/', '""', $expression) ?? '';

        // Remove subqueries and aggregate-function calls, arguments included:
        // those identifiers are aggregated or belong to another query scope.
        // Each pass may reveal new candidates, hence the loop.
        $previous = null;
        while ($previous !== $expression) {
            $previous   = $expression;
            $expression = $this->removeSubqueries($expression);
            $expression = $this->removeAggregateCalls($expression);
        }

        // Remove the names of the remaining scalar function calls (DATE, CONCAT, …)
        // so only their arguments are scanned for column references.
        $expression = (string) preg_replace('/\b[A-Za-z_][A-Za-z0-9_]*\s*(?=\()/', ' ', $expression);

        // Match optionally qualified, optionally backtick-quoted identifiers (upstream pattern).
        // Double-quote stripping above already removed string literals; remaining
        // identifiers may still use MySQL backticks. Bare table.column is also matched.
        if (!preg_match_all('/`?([A-Za-z_][A-Za-z0-9_]*)`?(?:\.`?([A-Za-z_][A-Za-z0-9_]*)`?)?/', $expression, $matches)) {
            return [];
        }

        $columns = [];
        foreach ($matches[0] as $index => $match) {
            if (in_array(strtoupper($matches[1][$index]), self::SQL_KEYWORD_TOKENS, true)) {
                continue;
            }

            $columns[] = '' !== $matches[2][$index]
                ? $matches[1][$index].'.'.$matches[2][$index]
                : $matches[1][$index];
        }

        return array_values(array_unique($columns));
    }

    /**
     * Removes every parenthesized group whose content starts with SELECT
     * (subqueries at any nesting depth); identifiers inside them belong to the
     * subquery's own scope, not to the outer GROUP BY.
     */
    private function removeSubqueries(string $expression): string
    {
        $pos = 0;
        while (false !== ($open = strpos($expression, '(', $pos))) {
            $close = $this->findMatchingParenthesis($expression, $open);
            if (-1 === $close) {
                break;
            }

            if (preg_match('/^\s*SELECT\b/i', substr($expression, $open + 1, $close - $open - 1))) {
                $expression = substr($expression, 0, $open).' '.substr($expression, $close + 1);
                $pos        = $open;
            } else {
                $pos = $open + 1;
            }
        }

        return $expression;
    }

    /**
     * Removes aggregate-function calls together with their arguments.
     */
    private function removeAggregateCalls(string $expression): string
    {
        $pattern = '/\b(?:'.implode('|', self::AGGREGATE_FUNCTIONS).')\s*\(/i';
        if (!preg_match_all($pattern, $expression, $matches, PREG_OFFSET_CAPTURE)) {
            return $expression;
        }

        // Remove from rightmost to leftmost so the offsets stay valid.
        foreach (array_reverse($matches[0]) as [$call, $offset]) {
            $open  = $offset + strlen($call) - 1;
            $close = $this->findMatchingParenthesis($expression, $open);
            if (-1 !== $close) {
                $expression = substr($expression, 0, $offset).' '.substr($expression, $close + 1);
            }
        }

        return $expression;
    }

    /**
     * Returns the index of the closing parenthesis matching the one at $open,
     * or -1 when unbalanced.
     */
    private function findMatchingParenthesis(string $expression, int $open): int
    {
        $length = strlen($expression);
        $depth  = 0;
        for ($i = $open; $i < $length; ++$i) {
            if ('(' === $expression[$i]) {
                ++$depth;
            } elseif (')' === $expression[$i]) {
                --$depth;
                if (0 === $depth) {
                    return $i;
                }
            }
        }

        return -1;
    }

    /**
     * Reduces a column reference to a comparable key: quoting, whitespace and
     * letter case are ignored, so `ph`.`url` / "ph"."url" matches ph.URL.
     */
    private function normalizeColumnIdentifier(string $column): string
    {
        return strtolower((string) preg_replace('/[`"\s]+/', '', $column));
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

                if (!$this->isEmptyValueSupportedCondition($exprFunction) && !is_array($filter['value']) && '' === trim((string) $filter['value'])) {
                    // Ignore empty values before applying glue so they do not create empty OR groups.
                    continue;
                }

                if (array_key_exists('glue', $filter) && 'or' === $filter['glue']) {
                    if ([] !== $andGroup) {
                        $orGroups[] = CompositeExpression::and(...$andGroup);
                        $andGroup   = [];
                    }
                }

                $tagCondition = $this->getTagCondition($filter);
                if ($tagCondition) {
                    $andGroup[] = $tagCondition;
                    continue;
                }

                $dncCondition = $this->getDncCondition($filter);
                if ($dncCondition) {
                    $andGroup[] = $dncCondition;
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
                        $columnValue = ":{$paramName}";
                        $expression  = $queryBuilder->expr()->or(
                            $queryBuilder->expr()->isNull($filter['column']),
                            $queryBuilder->expr()->{$exprFunction}($filter['column'], $columnValue)
                        );
                        $queryBuilder->setParameter($paramName, $filter['value']);
                        $andGroup[] = $expression;
                        break;
                    default:
                        $columnValue = ":{$paramName}";
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

                            case 'text':
                            case 'string':
                            case 'email':
                            case 'url':
                            case 'datetime':
                                switch ($exprFunction) {
                                    case 'like':
                                    case 'notLike':
                                        $filter['value'] = !str_contains($filter['value'], '%') ? '%'.$filter['value'].'%' : $filter['value'];
                                        break;
                                    case 'startsWith':
                                        $exprFunction    = 'like';
                                        $filter['value'] .= '%';
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

        if ([] !== $orGroups) {
            // Add the remaining $andGroup to the rest of the $orGroups if exists so we don't miss it.
            if ([] !== $andGroup) {
                $orGroups[] = CompositeExpression::and(...$andGroup);
            }

            if (1 === count($orGroups)) {
                $queryBuilder->andWhere($orGroups[0]);
            } else {
                $queryBuilder->andWhere(CompositeExpression::or(...$orGroups));
            }
        } elseif ([] !== $andGroup) {
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
        }
        if (in_array($filter['condition'], ['notIn', 'empty'])) {
            return $tagSubQuery->expr()->notIn('l.id', $tagSubQuery->getSQL());
        }

        return null;
    }

    /**
     * Get the Do Not Contact (DNC) condition for a query based on the provided filter.
     *
     * @param array{
     *     column: string,
     *     condition: string,
     *     value: string[]
     * } $filter The filter array containing 'column', 'condition', and 'value' keys
     */
    public function getDncCondition(array $filter): ?string
    {
        if ('dnc_preferences' !== $filter['column']) {
            return null;
        }

        // Handle empty/notEmpty conditions early to avoid unnecessary processing
        if (in_array($filter['condition'], ['empty', 'notEmpty'], true)) {
            $operator = 'empty' === $filter['condition'] ? 'NOT IN' : 'IN';

            return sprintf(
                'l.id %s (SELECT DISTINCT lead_id FROM %slead_donotcontact)',
                $operator,
                MAUTIC_TABLE_PREFIX
            );
        }

        // Parse and validate filter values
        $conditions = array_map(
            function (string $item): array {
                $parts = explode(':', $item);
                if (2 !== count($parts)) {
                    throw new \InvalidArgumentException('Invalid DNC filter format');
                }

                return [
                    'channel' => $this->db->quote($parts[0]),
                    'reason'  => (int) $parts[1],
                ];
            },
            $filter['value']
        );

        if ([] === $conditions) {
            return null;
        }

        // Build the subquery
        $dncSubQuery = $this->db->createQueryBuilder()
            ->select('DISTINCT lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'ldnc')
            ->where(implode(' OR ', array_map(
                fn (array $condition): string => sprintf(
                    '(ldnc.channel = %s AND ldnc.reason = %d)',
                    $condition['channel'],
                    $condition['reason']
                ),
                $conditions
            )));

        // Generate final condition
        $operator = 'in' === $filter['condition'] ? 'IN' : 'NOT IN';

        return sprintf('l.id %s (%s)', $operator, $dncSubQuery->getSQL());
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

    private function isEmptyValueSupportedCondition(string $condition): bool
    {
        return in_array($condition, ['empty', 'notEmpty', 'neq'], true);
    }
}
