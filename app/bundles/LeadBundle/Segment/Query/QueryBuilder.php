<?php

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;

/**
 * Segment query builder.
 *
 * DBAL 4 removed the query-part API (getQueryPart()/setQueryPart()/resetQueryPart())
 * and made the underlying state private, so this class keeps its own record of the
 * parts and generates the SELECT statement from it.
 *
 * That is not merely convenience: the segment engine rewrites joins after they have
 * been added (addJoinCondition(), replaceJoinCondition()), which DBAL 4 offers no way
 * to do. The part shapes are kept identical to DBAL 3's so the rest of the segment
 * code reads them unchanged.
 */
class QueryBuilder extends BaseQueryBuilder
{
    private ?ExpressionBuilder $_expr = null;

    /**
     * Unprocessed logic for segment processing.
     *
     * @var string[]|CompositeExpression[]
     */
    private array $logicStack = [];

    /**
     * Mirrors the structure DBAL 3 exposed through getQueryParts().
     *
     * @var array{select: string[], distinct: bool, from: array<int, array{table: string, alias: string|null, hint?: string}>, join: array<string, array<int, array{joinType: string, joinTable: string, joinAlias: string, joinCondition: string|null}>>, set: string[], where: string|CompositeExpression|null, groupBy: string[], having: string|CompositeExpression|null, orderBy: string[], values: array<string, mixed>}
     */
    private array $queryParts = self::EMPTY_QUERY_PARTS;

    private const array EMPTY_QUERY_PARTS = [
        'select'   => [],
        'distinct' => false,
        'from'     => [],
        'join'     => [],
        'set'      => [],
        'where'    => null,
        'groupBy'  => [],
        'having'   => null,
        'orderBy'  => [],
        'values'   => [],
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    public function expr(): ExpressionBuilder
    {
        if (null !== $this->_expr) {
            return $this->_expr;
        }

        $this->_expr = new ExpressionBuilder($this->connection);

        return $this->_expr;
    }

    public function setParameter($key, $value, $type = null): static
    {
        if (is_bool($value)) {
            $value = (int) $value;
        }

        parent::setParameter($key, $value, $type ?? \Doctrine\DBAL\ParameterType::STRING);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParts(): array
    {
        return $this->queryParts;
    }

    /**
     * @return mixed
     */
    public function getQueryPart(string $queryPartName)
    {
        return $this->queryParts[$queryPartName] ?? null;
    }

    /**
     * @param mixed $value
     */
    public function setQueryPart(string $queryPartName, $value): static
    {
        $this->queryParts[$queryPartName] = $value;

        return $this;
    }

    public function resetQueryPart(string $queryPartName): static
    {
        $this->queryParts[$queryPartName] = self::EMPTY_QUERY_PARTS[$queryPartName] ?? null;

        return $this;
    }

    public function resetQueryParts(?array $queryPartNames = null): static
    {
        foreach ($queryPartNames ?? array_keys(self::EMPTY_QUERY_PARTS) as $name) {
            $this->resetQueryPart($name);
        }

        return $this;
    }

    /**
     * SELECT is generated here; the other statement types are left to DBAL, which still
     * builds them from its own state.
     */
    private string $statementType = 'select';

    public function insert(string $table): static
    {
        $this->statementType = 'insert';
        parent::insert($table);

        return $this;
    }

    public function update(string $table): static
    {
        $this->statementType = 'update';
        parent::update($table);

        return $this;
    }

    public function delete(string $table): static
    {
        $this->statementType = 'delete';
        parent::delete($table);

        return $this;
    }

    /**
     * Compatibility shim for DBAL 3's add(), which Mautic uses to attach index hints to a
     * FROM clause - something the fluent API has never been able to express and DBAL 4
     * removed along with the rest of the query-part API.
     *
     * @param mixed $value
     */
    public function add(string $sqlPartName, $value, bool $append = false): static
    {
        if (!$append) {
            $this->queryParts[$sqlPartName] = $value;

            return $this;
        }

        if (is_array($this->queryParts[$sqlPartName] ?? null)) {
            $this->queryParts[$sqlPartName][] = $value;
        } else {
            $this->queryParts[$sqlPartName] = [$value];
        }

        return $this;
    }

    /**
     * DBAL 3 accepted either a variadic list or a single array; segment code still passes
     * arrays, so both are flattened here.
     */
    public function select(...$expressions): static
    {
        $this->queryParts['select'] = self::flatten($expressions);

        return $this;
    }

    public function addSelect(...$expressions): static
    {
        $this->queryParts['select'] = array_merge($this->queryParts['select'], self::flatten($expressions));

        return $this;
    }

    /**
     * @param array<mixed> $expressions
     *
     * @return string[]
     */
    private static function flatten(array $expressions): array
    {
        $flat = [];

        foreach ($expressions as $expression) {
            if (is_array($expression)) {
                $flat = array_merge($flat, array_map(strval(...), $expression));
            } else {
                $flat[] = (string) $expression;
            }
        }

        return $flat;
    }

    public function distinct(bool $distinct = true): static
    {
        $this->queryParts['distinct'] = $distinct;

        return $this;
    }

    public function from(string $table, ?string $alias = null): static
    {
        $this->queryParts['from'][] = ['table' => $table, 'alias' => $alias];

        return $this;
    }

    public function join(string $fromAlias, string $join, string $alias, ?string $condition = null): static
    {
        return $this->innerJoin($fromAlias, $join, $alias, $condition);
    }

    public function innerJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): static
    {
        return $this->addJoin('inner', $fromAlias, $join, $alias, $condition);
    }

    public function leftJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): static
    {
        return $this->addJoin('left', $fromAlias, $join, $alias, $condition);
    }

    public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): static
    {
        return $this->addJoin('right', $fromAlias, $join, $alias, $condition);
    }

    private function addJoin(string $type, string $fromAlias, string $join, string $alias, ?string $condition): static
    {
        $this->queryParts['join'][$fromAlias][] = [
            'joinType'      => $type,
            'joinTable'     => $join,
            'joinAlias'     => $alias,
            'joinCondition' => $condition,
        ];

        return $this;
    }

    public function where(...$predicates): static
    {
        $this->queryParts['where'] = 1 === count($predicates) ? $predicates[0] : CompositeExpression::and(...$predicates);


        // Keep DBAL's own state in step: it still builds UPDATE and DELETE.
        if (null !== $this->queryParts['where']) {
            parent::where($this->queryParts['where']);
        }
        return $this;
    }

    public function andWhere(...$predicates): static
    {
        $where = $this->queryParts['where'];

        if ($where instanceof CompositeExpression && CompositeExpression::TYPE_AND === $where->getType()) {
            // Match DBAL: append to an existing composite of the same type rather than
            // nesting a new one, so the generated SQL keeps the same grouping.
            $where = $where->with(...$predicates);
        } elseif (null !== $where) {
            $where = CompositeExpression::and($where, ...$predicates);
        } else {
            $where = 1 === count($predicates) ? $predicates[0] : CompositeExpression::and(...$predicates);
        }

        $this->queryParts['where'] = $where;


        // Keep DBAL's own state in step: it still builds UPDATE and DELETE.
        if (null !== $this->queryParts['where']) {
            parent::where($this->queryParts['where']);
        }
        return $this;
    }

    public function orWhere(...$predicates): static
    {
        $where = $this->queryParts['where'];

        if ($where instanceof CompositeExpression && CompositeExpression::TYPE_OR === $where->getType()) {
            // Match DBAL: append to an existing composite of the same type rather than
            // nesting a new one, so the generated SQL keeps the same grouping.
            $where = $where->with(...$predicates);
        } elseif (null !== $where) {
            $where = CompositeExpression::or($where, ...$predicates);
        } else {
            $where = 1 === count($predicates) ? $predicates[0] : CompositeExpression::or(...$predicates);
        }

        $this->queryParts['where'] = $where;


        // Keep DBAL's own state in step: it still builds UPDATE and DELETE.
        if (null !== $this->queryParts['where']) {
            parent::where($this->queryParts['where']);
        }
        return $this;
    }

    public function groupBy(...$expressions): static
    {
        $this->queryParts['groupBy'] = self::flatten($expressions);

        return $this;
    }

    public function addGroupBy(...$expressions): static
    {
        $this->queryParts['groupBy'] = array_merge($this->queryParts['groupBy'], self::flatten($expressions));

        return $this;
    }

    public function having(...$predicates): static
    {
        $this->queryParts['having'] = 1 === count($predicates) ? $predicates[0] : CompositeExpression::and(...$predicates);

        return $this;
    }

    public function andHaving(...$predicates): static
    {
        $having = $this->queryParts['having'];

        if ($having instanceof CompositeExpression && CompositeExpression::TYPE_AND === $having->getType()) {
            // Match DBAL: append to an existing composite of the same type rather than
            // nesting a new one, so the generated SQL keeps the same grouping.
            $having = $having->with(...$predicates);
        } elseif (null !== $having) {
            $having = CompositeExpression::and($having, ...$predicates);
        } else {
            $having = 1 === count($predicates) ? $predicates[0] : CompositeExpression::and(...$predicates);
        }

        $this->queryParts['having'] = $having;

        return $this;
    }

    public function orHaving(...$predicates): static
    {
        $having = $this->queryParts['having'];

        if ($having instanceof CompositeExpression && CompositeExpression::TYPE_OR === $having->getType()) {
            // Match DBAL: append to an existing composite of the same type rather than
            // nesting a new one, so the generated SQL keeps the same grouping.
            $having = $having->with(...$predicates);
        } elseif (null !== $having) {
            $having = CompositeExpression::or($having, ...$predicates);
        } else {
            $having = 1 === count($predicates) ? $predicates[0] : CompositeExpression::or(...$predicates);
        }

        $this->queryParts['having'] = $having;

        return $this;
    }

    public function orderBy(string $sort, ?string $order = null): static
    {
        $this->queryParts['orderBy'] = [$sort.' '.($order ?? 'ASC')];

        return $this;
    }

    public function addOrderBy(string $sort, ?string $order = null): static
    {
        $this->queryParts['orderBy'][] = $sort.' '.($order ?? 'ASC');

        return $this;
    }

    public function getSQL(): string
    {
        if ('select' !== $this->statementType) {
            return parent::getSQL();
        }

        $sqlParts = $this->queryParts;

        $query = 'SELECT '.($sqlParts['distinct'] ? 'DISTINCT ' : '').
            implode(', ', (array) $sqlParts['select']);

        $query .= ($sqlParts['from'] ? ' FROM '.implode(', ', $this->getFromClauses()) : '')
            .(null !== $sqlParts['where'] ? ' WHERE '.$sqlParts['where'] : '')
            .($sqlParts['groupBy'] ? ' GROUP BY '.implode(', ', (array) $sqlParts['groupBy']) : '')
            .(null !== $sqlParts['having'] ? ' HAVING '.$sqlParts['having'] : '')
            .($sqlParts['orderBy'] ? ' ORDER BY '.implode(', ', (array) $sqlParts['orderBy']) : '');

        if (null !== $this->getMaxResults() || 0 !== $this->getFirstResult()) {
            return $this->connection->getDatabasePlatform()->modifyLimitQuery(
                $query,
                $this->getMaxResults(),
                $this->getFirstResult()
            );
        }

        return $query;
    }

    /**
     * @return string[]
     */
    private function getFromClauses(): array
    {
        $fromClauses  = [];
        $knownAliases = [];

        // Loop through all FROM clauses
        foreach ($this->queryParts['from'] as $from) {
            if (null === $from['alias']) {
                $tableSql       = $from['table'];
                $tableReference = $from['table'];
            } else {
                $tableSql       = $from['table'].' '.$from['alias'];
                $tableReference = $from['alias'];
            }

            if (isset($from['hint'])) {
                $tableSql .= ' '.$from['hint'];
            }

            $knownAliases[$tableReference] = true;

            $fromClauses[$tableReference] = $tableSql.$this->getSQLForJoins($tableReference, $knownAliases);
        }

        $this->verifyAllAliasesAreKnown($knownAliases);

        return $fromClauses;
    }

    /**
     * @param array<string, true> $knownAliases
     *
     * @throws QueryException
     */
    private function getSQLForJoins(string $fromAlias, array &$knownAliases): string
    {
        $sql = '';

        if (!isset($this->queryParts['join'][$fromAlias])) {
            return $sql;
        }

        foreach ($this->queryParts['join'][$fromAlias] as $join) {
            if (array_key_exists($join['joinAlias'], $knownAliases)) {
                throw QueryException::nonUniqueAlias($join['joinAlias'], array_keys($knownAliases));
            }

            $sql .= ' '.strtoupper($join['joinType']).' JOIN '.$join['joinTable'].' '.$join['joinAlias'];

            if (null !== $join['joinCondition']) {
                $sql .= ' ON '.$join['joinCondition'];
            }

            $knownAliases[$join['joinAlias']] = true;
        }

        foreach ($this->queryParts['join'][$fromAlias] as $join) {
            $sql .= $this->getSQLForJoins($join['joinAlias'], $knownAliases);
        }

        return $sql;
    }

    /**
     * @param array<string, true> $knownAliases
     *
     * @throws QueryException
     */
    private function verifyAllAliasesAreKnown(array $knownAliases): void
    {
        foreach ($this->queryParts['join'] as $fromAlias => $joins) {
            if (!isset($knownAliases[$fromAlias])) {
                throw QueryException::unknownAlias($fromAlias, array_keys($knownAliases));
            }
        }
    }

    public function getJoinCondition(string $alias): string|false
    {
        $parts = $this->getQueryParts();
        foreach ($parts['join']['l'] as $joinedTable) {
            if ($joinedTable['joinAlias'] == $alias) {
                return $joinedTable['joinCondition'];
            }
        }

        return false;
    }

    /**
     * Add AND condition to existing table alias.
     *
     * @throws QueryException
     */
    public function addJoinCondition($alias, $expr): static
    {
        $result = $parts = $this->getQueryPart('join');

        foreach ($parts as $tbl => $joins) {
            foreach ($joins as $key => $join) {
                if ($join['joinAlias'] == $alias) {
                    $result[$tbl][$key]['joinCondition'] = $join['joinCondition'].' and ('.$expr.')';
                    $inserted                            = true;
                }
            }
        }

        if (!isset($inserted)) {
            throw new QueryException('Inserting condition to nonexistent join '.$alias);
        }

        $this->setQueryPart('join', $result);

        return $this;
    }

    public function replaceJoinCondition($alias, $expr): static
    {
        $parts = $this->getQueryPart('join');
        foreach ($parts['l'] as $key => $part) {
            if ($part['joinAlias'] == $alias) {
                $parts['l'][$key]['joinCondition'] = $expr;
            }
        }

        $this->setQueryPart('join', $parts);

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function setParametersPairs($parameters, $filterParameters)
    {
        if (!is_array($parameters)) {
            return $this->setParameter($parameters, $filterParameters);
        }

        foreach ($parameters as $parameter) {
            $parameterValue = array_shift($filterParameters);
            $this->setParameter($parameter, $parameterValue);
        }

        return $this;
    }

    public function getTableAlias(string $table, $joinType = null): array|bool|string
    {
        if (null === $joinType) {
            $tables = $this->getTableAliases();

            return $tables[$table] ?? false;
        }

        $tableJoins = $this->getTableJoins($table);

        foreach ($tableJoins as $tableJoin) {
            if ($tableJoin['joinType'] == $joinType) {
                return $tableJoin['joinAlias'];
            }
        }

        return false;
    }

    /**
     * @return mixed[]
     */
    public function getTableJoins(string $tableName): array
    {
        $found = [];
        foreach ($this->getQueryParts()['join'] as $join) {
            foreach ($join as $joinPart) {
                if ($tableName == $joinPart['joinTable']) {
                    $found[] = $joinPart;
                }
            }
        }

        return count($found) ? $found : [];
    }

    /**
     * Functions returns either the 'lead.id' or the primary key from right joined table.
     */
    public function guessPrimaryLeadContactIdColumn(): string
    {
        $parts     = $this->getQueryParts();
        $leadTable = $parts['from'][0]['alias'];

        if ('orp' === $leadTable) {
            return 'orp.lead_id';
        }

        if (!isset($parts['join'][$leadTable])) {
            return $leadTable.'.id';
        }

        $joins     = $parts['join'][$leadTable];

        foreach ($joins as $join) {
            if ('right' == $join['joinType']) {
                $matches = null;
                if (preg_match('/'.$leadTable.'\.id \= ([^\ ]+)/i', $join['joinCondition'], $matches)) {
                    return $matches[1];
                }
            }
        }

        return $leadTable.'.id';
    }

    /**
     * Return aliases of all currently registered tables.
     *
     * @return array
     */
    public function getTableAliases()
    {
        $queryParts = $this->getQueryParts();
        $tables     = array_reduce($queryParts['from'], function (array $result, array $item): array {
            $result[$item['table']] = $item['alias'];

            return $result;
        }, []);

        foreach ($queryParts['join'] as $join) {
            foreach ($join as $joinPart) {
                $tables[$joinPart['joinTable']] = $joinPart['joinAlias'];
            }
        }

        return $tables;
    }

    /**
     * @param string $table
     */
    public function isJoinTable($table): bool
    {
        $queryParts = $this->getQueryParts();

        foreach ($queryParts['join'] as $join) {
            foreach ($join as $joinPart) {
                if ($joinPart['joinTable'] == $table) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return mixed|string
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDebugOutput()
    {
        $params = $this->getParameters();
        $sql    = $this->getSQL();
        foreach ($params as $key=>$val) {
            if (!is_int($val) && !is_float($val) && !is_array($val)) {
                $val = "'{$val}'";
            } elseif (is_array($val)) {
                if (ArrayParameterType::STRING === $this->getParameterType($key)) {
                    $val = array_map(static fn ($value): string => "'{$value}'", $val);
                }
                $val = implode(', ', $val);
            }
            $sql = str_replace(":{$key}", $val, $sql);
        }

        return $sql;
    }

    public function hasLogicStack(): bool
    {
        return count($this->logicStack) > 0;
    }

    /**
     * @return string[]|CompositeExpression[]
     */
    public function getLogicStack(): array
    {
        return $this->logicStack;
    }

    public function popLogicStack(): array
    {
        $stack            = $this->logicStack;
        $this->logicStack = [];

        return $stack;
    }

    private function addLogicStack(string|CompositeExpression $expression): static
    {
        $this->logicStack[] = $expression;

        return $this;
    }

    /**
     * This function assembles correct logic for segment processing, this is to replace andWhere and orWhere (virtualy
     *  as they need to be kept). You may not use andWhere in filters!!!
     */
    public function addLogic($expression, $glue): void
    {
        // little setup
        $glue = strtolower($glue);

        //  Different handling
        if ('or' === $glue) {
            //  Is this the first condition in query builder?
            if (null !== $this->getQueryPart('where')) {
                // Are the any queued conditions?
                if ($this->hasLogicStack()) {
                    // We need to apply current stack to the query builder
                    $this->applyStackLogic();
                }
                // We queue current expression to stack
                $this->addLogicStack($expression);
            } else {
                $this->andWhere($expression);
            }
        } else {
            //  Glue is AND
            if ($this->hasLogicStack()) {
                $this->addLogicStack($expression);
            } else {
                $this->andWhere($expression);
            }
        }
    }

    /**
     * Apply content of stack.
     */
    public function applyStackLogic(): static
    {
        if ($this->hasLogicStack()) {
            $stackGroupExpression = CompositeExpression::and(...$this->popLogicStack());
            $this->orWhere($stackGroupExpression);
        }

        return $this;
    }

    public function createQueryBuilder(?Connection $connection = null): self
    {
        return new self($connection ?: $this->connection);
    }
}
