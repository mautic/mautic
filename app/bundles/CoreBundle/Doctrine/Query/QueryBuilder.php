<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Mautic\CoreBundle\Exception\DbalException;

/**
 * A DBAL query builder that remembers what it was given.
 *
 * DBAL 4 removed the query-part API (getQueryPart()/setQueryPart()/resetQueryPart())
 * and made the underlying state private, so a query can no longer be read back or
 * rewritten once built. Mautic relies on both: resolving a table name from an alias,
 * attaching MySQL index hints to a FROM clause, and rewriting join conditions after
 * the fact.
 *
 * The parts are recorded in the same shapes DBAL 3 exposed, so existing callers read
 * them unchanged, and SELECT is generated from them. INSERT, UPDATE and DELETE are
 * left to DBAL, which still builds those from its own state.
 */
class QueryBuilder extends BaseQueryBuilder
{
    protected const array EMPTY_QUERY_PARTS = [
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

    /**
     * @var array<string, mixed>
     */
    protected array $queryParts = self::EMPTY_QUERY_PARTS;

    /**
     * Only SELECT is generated here; see the class docblock.
     */
    protected string $statementType = 'select';

    public function __construct(
        protected readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParts(): array
    {
        return $this->queryParts;
    }

    public function getQueryPart(string $queryPartName): mixed
    {
        return $this->queryParts[$queryPartName] ?? null;
    }

    public function setQueryPart(string $queryPartName, mixed $value): static
    {
        $this->queryParts[$queryPartName] = $value;

        return $this;
    }

    public function resetQueryPart(string $queryPartName): static
    {
        $this->queryParts[$queryPartName] = self::EMPTY_QUERY_PARTS[$queryPartName] ?? null;

        return $this;
    }

    /**
     * @param string[]|null $queryPartNames
     */
    public function resetQueryParts(?array $queryPartNames = null): static
    {
        foreach ($queryPartNames ?? array_keys(self::EMPTY_QUERY_PARTS) as $name) {
            $this->resetQueryPart($name);
        }

        return $this;
    }

    /**
     * Compatibility shim for DBAL 3's add(). Mautic uses it to attach MySQL index hints
     * to a FROM clause, which the fluent API has never been able to express.
     */
    public function add(string $sqlPartName, mixed $value, bool $append = false): static
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

    
    public function select(string ...$expressions): static
    {
        $this->queryParts['select'] = $expressions;

        return $this;
    }

    public function addSelect(string $expression, string ...$expressions): static
    {
        $this->queryParts['select'] = array_merge($this->queryParts['select'], [$expression], $expressions);

        return $this;
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

    protected function addJoin(string $type, string $fromAlias, string $join, string $alias, ?string $condition): static
    {
        $this->queryParts['join'][$fromAlias][] = [
            'joinType'      => $type,
            'joinTable'     => $join,
            'joinAlias'     => $alias,
            'joinCondition' => $condition,
        ];

        return $this;
    }

    public function where(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): static
    {
        $this->queryParts['where'] = [] === $predicates ? $predicate : CompositeExpression::and($predicate, ...$predicates);
        $this->syncWhereWithDbal();

        return $this;
    }

    public function andWhere(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): static
    {
        $this->queryParts['where'] = $this->combine($this->queryParts['where'], CompositeExpression::TYPE_AND, [$predicate, ...$predicates]);
        $this->syncWhereWithDbal();

        return $this;
    }

    public function orWhere(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): static
    {
        $this->queryParts['where'] = $this->combine($this->queryParts['where'], CompositeExpression::TYPE_OR, [$predicate, ...$predicates]);
        $this->syncWhereWithDbal();

        return $this;
    }

    public function groupBy(string $expression, string ...$expressions): static
    {
        $this->queryParts['groupBy'] = [$expression, ...$expressions];

        return $this;
    }

    public function addGroupBy(string $expression, string ...$expressions): static
    {
        $this->queryParts['groupBy'] = array_merge($this->queryParts['groupBy'], [$expression], $expressions);

        return $this;
    }

    public function having(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): static
    {
        $this->queryParts['having'] = [] === $predicates ? $predicate : CompositeExpression::and($predicate, ...$predicates);

        return $this;
    }

    public function andHaving(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): static
    {
        $this->queryParts['having'] = $this->combine($this->queryParts['having'], CompositeExpression::TYPE_AND, [$predicate, ...$predicates]);

        return $this;
    }

    public function orHaving(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): static
    {
        $this->queryParts['having'] = $this->combine($this->queryParts['having'], CompositeExpression::TYPE_OR, [$predicate, ...$predicates]);

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
    protected function getFromClauses(): array
    {
        $fromClauses  = [];
        $knownAliases = [];

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
     */
    protected function getSQLForJoins(string $fromAlias, array &$knownAliases): string
    {
        $sql = '';

        if (!isset($this->queryParts['join'][$fromAlias])) {
            return $sql;
        }

        foreach ($this->queryParts['join'][$fromAlias] as $join) {
            if (array_key_exists($join['joinAlias'], $knownAliases)) {
                throw $this->nonUniqueAliasException($join['joinAlias'], array_keys($knownAliases));
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
     */
    protected function verifyAllAliasesAreKnown(array $knownAliases): void
    {
        foreach ($this->queryParts['join'] as $fromAlias => $joins) {
            if (!isset($knownAliases[$fromAlias])) {
                throw $this->unknownAliasException((string) $fromAlias, array_keys($knownAliases));
            }
        }
    }

    /**
     * Appends to an existing composite of the same type rather than nesting a new one,
     * matching DBAL - otherwise the SQL grows redundant parentheses.
     *
     * @param array<int, string|CompositeExpression> $predicates
     */
    protected function combine(string|CompositeExpression|null $current, string $type, array $predicates): string|CompositeExpression
    {
        if ($current instanceof CompositeExpression && $type === $current->getType()) {
            return $current->with(...$predicates);
        }

        $factory = CompositeExpression::TYPE_AND === $type ? CompositeExpression::and(...) : CompositeExpression::or(...);

        if (null !== $current) {
            return $factory($current, ...$predicates);
        }

        return 1 === count($predicates) ? $predicates[0] : $factory(...$predicates);
    }

    /**
     * DBAL still builds UPDATE and DELETE from its own state, so the WHERE has to reach it.
     */
    protected function syncWhereWithDbal(): void
    {
        if (null !== $this->queryParts['where']) {
            parent::where($this->queryParts['where']);
        }
    }


    /**
     * @param string[] $knownAliases
     */
    protected function nonUniqueAliasException(string $alias, array $knownAliases): \Throwable
    {
        return new DbalException(sprintf(
            'The given alias "%s" is not unique in FROM and JOIN clause table. The currently registered aliases are: %s.',
            $alias,
            implode(', ', $knownAliases)
        ));
    }

    /**
     * @param string[] $knownAliases
     */
    protected function unknownAliasException(string $alias, array $knownAliases): \Throwable
    {
        return new DbalException(sprintf(
            'The given alias "%s" is not part of any FROM or JOIN clause table. The currently registered aliases are: %s.',
            $alias,
            implode(', ', $knownAliases)
        ));
    }
}
