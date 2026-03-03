<?php

namespace Mautic\ReportBundle\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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

    private ?string $contentTemplate = null;

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private Connection $db,
        private Report $entity,
        private ChannelListHelper $channelListHelper,
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

        $orderByColumns = [];
        // Build ORDER BY clause
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
                        $orderByColumns[] = $order;
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
                    $orderByColumns[] = $o['column'];
                } elseif (!empty($o['column'])) {
                    $queryBuilder->orderBy($o['column'], $o['direction']);
                    $orderByColumns[] = $o['column'];
                }
            }
        }

        // Build GROUP BY
        $groupByColumns = [];
        if ($groupByOptions = $this->entity->getGroupBy()) {
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

        // Get any existing GROUP BY columns from the query builder
        $existingGroupBy = $queryBuilder->getQueryPart('groupBy') ?: [];
        $groupByColumns  = array_merge($groupByColumns, (array) $existingGroupBy);

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
                        $selectText .= ' AS '.$this->db->quoteIdentifier($fieldOptions['alias']);
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
        $aggregators       = $this->entity->getAggregators();
        $aggregatorSelect  = [];
        $aggregatedColumns = [];

        if ($aggregators && $groupByOptions) {
            $isPostgreSql = $this->db->getDatabasePlatform() instanceof PostgreSQLPlatform;
            foreach ($aggregators as $aggregator) {
                if (isset($options['columns'][$aggregator['column']]) && isset($options['columns'][$aggregator['column']]['formula'])) {
                    $columnSelect = $options['columns'][$aggregator['column']]['formula'];
                } else {
                    $columnSelect = $aggregator['column'];
                }

                // Recursively sanitize inner column references
                $innerExpression = $this->sanitizeExpression($columnSelect);

                switch ($aggregator['function']) {
                    case 'AVG': // PostgreSQL and MySQL default AVG precision is different
                        $appendix   = $isPostgreSql ? '::numeric(10, 4)' : '';
                        $selectText = sprintf('%s(%s)%s', $aggregator['function'], $innerExpression, $appendix);
                        break;
                    default:
                        $selectText = sprintf('%s(%s)', $aggregator['function'], $innerExpression);
                }
                $alias               = sprintf('%s %s', $aggregator['function'], $aggregator['column']);
                $quotedAlias         = $this->sanitizeColumnName($alias, true);
                $aggregatorSelect[]  = sprintf('%s AS %s', $selectText, $quotedAlias);
                $aggregatedColumns[] = $columnSelect; // Track aggregated columns
            }

            $queryBuilder->addSelect($aggregatorSelect);
        }

        // Ensure all non-aggregated SELECT/ORDER columns are in GROUP BY
        $allSelectColumns = array_merge(array_merge($selectColumns, $event->getSelectColumns() ?: []), $orderByColumns);
        if ($allSelectColumns && ($groupByColumns || $existingGroupBy || $aggregators)) {
            $nonAggregatedColumns = [];

            // 1. Normalize GROUP BY columns
            $normalizedGroupBy = array_map([$this, 'normalizeColumnName'], (array) $groupByColumns);

            // 2. Normalize aggregated columns
            $normalizedAggregated = array_map([$this, 'normalizeColumnName'], $aggregatedColumns);

            // 3. Extract base column names from selectColumns
            foreach ($allSelectColumns as $select) {
                $columns = $this->extractColumnsFromSelect($select);

                foreach ($columns as $column) {
                    // Normalize for comparison
                    $normalizedColumn = $this->normalizeColumnName($column);

                    // Skip if already in GROUP BY or aggregated (using normalized comparison)
                    if (in_array($normalizedColumn, $normalizedGroupBy) || in_array($normalizedColumn, $normalizedAggregated)) {
                        continue;
                    }

                    $nonAggregatedColumns[] = $column;  // Add the original (usually sanitized) version
                }
            }

            // 4. Add missing non-aggregated columns to groupByColumns
            $groupByColumns = array_merge($groupByColumns, $nonAggregatedColumns);
            $queryBuilder->resetQueryPart('groupBy'); // Clear existing GROUP BY
            if ($groupByColumns) {
                $queryBuilder->addGroupBy($groupByColumns); // Add updated GROUP BY
            }
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
        } elseif ($andGroup) {
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
            function (string $item) {
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

        if (empty($conditions)) {
            return null;
        }

        // Build the subquery
        $dncSubQuery = $this->db->createQueryBuilder()
            ->select('DISTINCT lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'ldnc')
            ->where(implode(' OR ', array_map(
                fn ($condition) => sprintf(
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
     * Extracts qualified column references (table.column) from a SELECT expression.
     *
     * - Preserves original quoting (backticks for MySQL, double quotes for PostgreSQL, or unquoted).
     * - Handles simple columns: e.subject, `e`.`subject`, "e"."subject"
     * - Handles columns inside common functions: CONCAT, IFNULL, IF, ROUND, CASE, etc.
     * - Skips pure aggregates/constants with no real columns: COUNT(*), SUM(1), 'literal', 123
     * - Completely skips any column references that appear inside subqueries (including correlated references like e.id inside a scalar subquery)
     * - Handles nested parentheses and multiple subqueries correctly
     * - Returns array of unique matched column strings (with original quoting preserved), in order of first appearance.
     *
     * @param string $selectExpression The raw SELECT part
     *
     * @return array<string>
     */
    private function extractColumnsFromSelect(string $selectExpression): array
    {
        $expr = preg_replace('/\s+AS\s+.*$/i', '', trim($selectExpression));

        // Quick skip for pure constants/literals/simple aggregates with no columns
        if (preg_match('/^\s*((\d+\.?\d*)|(COUNT|SUM|AVG|MIN|MAX|SELECT)\s*\(\s*(\*|\d+)\s*\))\s*$/ix', $expr)) {
            return [];
        }

        // Find all top-level subquery ranges: (SELECT ... ) – including nested ones inside
        $ranges = [];
        $length = strlen($expr);
        $i      = 0;
        while ($i < $length) {
            if ('(' === $expr[$i]) {
                $j = $i + 1;
                // Skip whitespace (spaces, tabs, newlines, etc.)
                while ($j < $length && ctype_space($expr[$j])) {
                    ++$j;
                }
                // Check for "SELECT" (case-insensitive)
                if ($j + 6 <= $length && 'SELECT' === strtoupper(substr($expr, $j, 6))) {
                    // This is a subquery – find the matching closing parenthesis
                    $level = 1;
                    $k     = $i + 1;
                    while ($k < $length && $level > 0) {
                        if ('(' === $expr[$k]) {
                            ++$level;
                        } elseif (')' === $expr[$k]) {
                            --$level;
                        }
                        ++$k;
                    }
                    if (0 === $level) {
                        // Valid subquery range: from opening ( to closing )
                        $ranges[] = [$i, $k - 1];
                        $i        = $k; // Skip past the closing parenthesis
                        continue;
                    }
                    // Unbalanced – ignore and continue searching
                }
            }
            ++$i;
        }

        // Extract potential qualified columns with their positions
        // Improved pattern: identifiers must start with letter or _, to avoid invalid matches
        $pattern          = '/([`"]?[a-zA-Z_][a-zA-Z0-9_]*[`"]?\.[`"]?[a-zA-Z_][a-zA-Z0-9_]*[`"]?)/i';
        $potentialMatches = [];
        if (preg_match_all($pattern, $expr, $matches, PREG_OFFSET_CAPTURE)) {
            $potentialMatches = $matches[1]; // [[column_string, offset], ...]
        }

        // Filter out any matches that fall inside a subquery range
        $columns = [];
        foreach ($potentialMatches as $match) {
            $col = $match[0];
            $pos = $match[1];

            $isInsideSubquery = false;
            foreach ($ranges as $range) {
                // If the start of the match is inside any subquery range → skip it
                if ($pos >= $range[0] && $pos <= $range[1]) {
                    $isInsideSubquery = true;
                    break;
                }
            }

            if (!$isInsideSubquery) {
                $columns[] = $col;
            }
        }

        // Unique while preserving first-occurrence order
        $uniqueColumns = [];
        foreach ($columns as $col) {
            if (!in_array($col, $uniqueColumns, true)) {
                $uniqueColumns[] = $col;
            }
        }

        return $uniqueColumns;
    }

    /**
     * Sanitizes expressions recursively.
     * - If the expression starts with a function (AVG(, IF(, ROUND(, etc.) or contains SELECT,
     *   it recurses into the arguments and sanitizes only real column references (table.column).
     * - Simple labels/aliases that are not expressions are left untouched.
     */
    private function sanitizeExpression(string $expression): string
    {
        $trimmed = trim($expression);

        // If it's a simple label/alias (no function, no parentheses, no SELECT), leave it as-is
        if (!$this->isComplexExpression($trimmed)) {
            if (preg_match('/([`"]?[a-zA-Z_][a-zA-Z0-9_]*[`"]?\.[`"]?[a-zA-Z_][a-zA-Z0-9_]*[`"]?)/i', $trimmed)) {
                return $this->sanitizeColumnName($trimmed); // if its simple column, we sanitize it, otherwise its inner query
            }

            return $trimmed;
        }

        // Recursively sanitize all column references inside the expression
        return preg_replace_callback(
            '/([`"]?[a-zA-Z_][a-zA-Z0-9_]*[`"]?\.[`"]?[a-zA-Z_][a-zA-Z0-9_]*[`"]?)/i',
            fn ($matches) => $this->sanitizeColumnName($matches[1]),
            $trimmed
        );
    }

    /**
     * Returns true if the string looks like a complex SQL expression (function call or SELECT).
     */
    private function isComplexExpression(string $expression): bool
    {
        $expr = trim($expression);

        // Starts with a function name followed by '(' or contains SELECT
        return preg_match('/^\w+\s*\(/i', $expr) || 0 === stripos($expr, 'SELECT');
    }

    /**
     * We must sanitize the table aliases as they might be auto generated.
     * Aliases like "8e296a06" makes MySql to think it is a number.
     * Expects param in format "table_alias.column_name".
     */
    private function sanitizeColumnName(string $fullColumnName, bool $isLabel = false): string
    {
        if ($isLabel) {
            return $this->db->getDatabasePlatform() instanceof PostgreSQLPlatform
                ? "\"$fullColumnName\""
                : "`$fullColumnName`";
        }

        [$tableAlias, $columnName] = explode('.', $fullColumnName);

        return $this->db->getDatabasePlatform() instanceof PostgreSQLPlatform
            ? "\"$tableAlias\".\"$columnName\""
            : "`$tableAlias`.`$columnName`";
    }

    /**
     * Normalize a column identifier for comparison by removing platform-specific identifier quotes
     * if it appears to be a simple (non-complex) column reference.
     */
    private function normalizeColumnName(string $fullColumnName): string
    {
        if ($this->db->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return preg_match('/^["a-zA-Z0-9_\.\$]+$/', $fullColumnName)
                ? str_replace('"', '', $fullColumnName)
                : $fullColumnName;
        }

        return preg_match('/^[`a-zA-Z0-9_\.\$]+$/', $fullColumnName)
            ? str_replace('`', '', $fullColumnName)
            : $fullColumnName;
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
