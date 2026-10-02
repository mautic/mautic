<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\ForUpdate;
use Doctrine\DBAL\Query\ForUpdate\ConflictResolutionMode;
use Doctrine\DBAL\Query\Limit;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Doctrine\DBAL\Query\SelectQuery;
use Doctrine\DBAL\Query\UnionType;
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

    /**
     * The parent keeps its own copy private, so the requested row lock is tracked here.
     */
    protected ?ForUpdate $forUpdateLock = null;

    /**
     * DBAL keeps its own connection private, so this class holds its own reference for
     * generating SELECT. Declared and assigned rather than promoted: a constructor whose
     * body is only parent::__construct() is removed by
     * RemoveParentDelegatingConstructorRector, which does not account for promotion.
     */
    protected readonly Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);

        $this->connection = $connection;
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
     * DBAL 4's targeted resets have to clear the tracked part as well, or the clause
     * lives on in the SQL this builder generates.
     */
    public function resetWhere(): static
    {
        parent::resetWhere();
        $this->resetQueryPart('where');

        return $this;
    }

    public function resetGroupBy(): static
    {
        parent::resetGroupBy();
        $this->resetQueryPart('groupBy');

        return $this;
    }

    public function resetHaving(): static
    {
        parent::resetHaving();
        $this->resetQueryPart('having');

        return $this;
    }

    public function resetOrderBy(): static
    {
        parent::resetOrderBy();
        $this->resetQueryPart('orderBy');

        return $this;
    }

    /**
     * Compatibility shim for DBAL 3's add(). Mautic uses it to attach MySQL index hints
     * to a FROM clause, which the fluent API has never been able to express, and to put
     * a detached join back under the alias it belongs to.
     *
     * The branches mirror DBAL 3 because callers depend on the shapes it produced: the
     * flat parts take each element, a part keyed by an alias - ['l' => $join] - appends
     * beneath that alias rather than beside it, and anything else is appended whole.
     */
    public function add(string $sqlPartName, mixed $value, bool $append = false): static
    {
        $isArray    = is_array($value);
        $isMultiple = is_array($this->queryParts[$sqlPartName] ?? null);

        if ($isMultiple && !$isArray) {
            $value = [$value];
        }

        if (!$append) {
            $this->queryParts[$sqlPartName] = $value;

            return $this;
        }

        if (in_array($sqlPartName, ['orderBy', 'groupBy', 'select', 'set'], true)) {
            foreach ($value as $part) {
                $this->queryParts[$sqlPartName][] = $part;
            }
        } elseif ($isArray && [] !== $value && is_array($value[key($value)])) {
            $key                                    = key($value);
            $this->queryParts[$sqlPartName][$key][] = $value[$key];
        } elseif ($isMultiple) {
            $this->queryParts[$sqlPartName][] = $value;
        } else {
            $this->queryParts[$sqlPartName] = $value;
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

    /**
     * DBAL switches the builder back to a SELECT here, so the tracked type has to follow.
     * Without it a builder reused after insert()/update()/delete() keeps generating that
     * write statement, and the intended read executes it instead.
     */
    public function select(string ...$expressions): static
    {
        $this->statementType        = 'select';
        $this->queryParts['select'] = $expressions;

        return $this;
    }

    public function addSelect(string $expression, string ...$expressions): static
    {
        $this->statementType        = 'select';
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
        $this->addJoin('inner', $fromAlias, $join, $alias, $condition);

        return $this;
    }

    public function leftJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): static
    {
        $this->addJoin('left', $fromAlias, $join, $alias, $condition);

        return $this;
    }

    public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): static
    {
        $this->addJoin('right', $fromAlias, $join, $alias, $condition);

        return $this;
    }

    protected function addJoin(string $type, string $fromAlias, string $join, string $alias, ?string $condition): void
    {
        $this->queryParts['join'][$fromAlias][] = [
            'joinType'      => $type,
            'joinTable'     => $join,
            'joinAlias'     => $alias,
            'joinCondition' => $condition,
        ];
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

    /**
     * The tracked parts are handed to the platform's own SELECT builder rather than
     * concatenated here, so the row lock, the limit and the platform-specific spellings
     * of all three come out the way DBAL would write them.
     */
    public function getSQL(): string
    {
        if ('select' !== $this->statementType) {
            return parent::getSQL();
        }

        $sqlParts = $this->queryParts;

        return $this->connection->getDatabasePlatform()->createSelectSQLBuilder()->buildSQL(
            new SelectQuery(
                (bool) $sqlParts['distinct'],
                (array) $sqlParts['select'],
                $sqlParts['from'] ? array_values($this->getFromClauses()) : [],
                null !== $sqlParts['where'] ? (string) $sqlParts['where'] : null,
                (array) $sqlParts['groupBy'],
                null !== $sqlParts['having'] ? (string) $sqlParts['having'] : null,
                (array) $sqlParts['orderBy'],
                new Limit($this->getMaxResults(), $this->getFirstResult()),
                $this->forUpdateLock,
            )
        );
    }

    /**
     * DBAL keeps the requested lock private, so it is tracked here as well; without it
     * getSQL() would hand back a query the caller believes is locked when it is not.
     */
    public function forUpdate(ConflictResolutionMode $conflictResolutionMode = ConflictResolutionMode::ORDINARY): static
    {
        parent::forUpdate($conflictResolutionMode);

        $this->forUpdateLock = new ForUpdate($conflictResolutionMode);

        return $this;
    }

    /**
     * UNION and common table expressions restructure the whole statement, which a builder
     * that generates SELECT from tracked parts cannot express. They are refused rather
     * than dropped silently, and deliberately not as a DBAL exception - callers catch
     * those to fall back, which would hide the refusal again.
     */
    public function union(string|BaseQueryBuilder $part): static
    {
        throw $this->unsupported(__FUNCTION__);
    }

    public function addUnion(string|BaseQueryBuilder $part, UnionType $type = UnionType::DISTINCT): static
    {
        throw $this->unsupported(__FUNCTION__);
    }

    /**
     * @param string[]|null $columns
     */
    public function with(string $name, string|BaseQueryBuilder $part, ?array $columns = null): static
    {
        throw $this->unsupported(__FUNCTION__);
    }

    private function unsupported(string $method): \LogicException
    {
        return new \LogicException(sprintf(
            '%s::%s() is not supported. This query builder generates SELECT from the parts it tracks, which cannot express UNION or common table expressions. Build such a query with %s instead.',
            self::class,
            $method,
            BaseQueryBuilder::class
        ));
    }

    /**
     * @return string[]
     */
    private function getFromClauses(): array
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
     * @param array<array-key, true> $knownAliases
     */
    private function getSQLForJoins(string $fromAlias, array &$knownAliases): string
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
     * @param array<array-key, true> $knownAliases
     */
    private function verifyAllAliasesAreKnown(array $knownAliases): void
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
     * @param list<array-key> $knownAliases
     */
    protected function nonUniqueAliasException(string $alias, array $knownAliases): \Throwable // @phpstan-ignore typePerfect.narrowReturnObjectType (Segment QueryBuilder child returns QueryException)
    {
        return new DbalException(sprintf(
            'The given alias "%s" is not unique in FROM and JOIN clause table. The currently registered aliases are: %s.',
            $alias,
            implode(', ', $knownAliases)
        ));
    }

    /**
     * @param list<array-key> $knownAliases
     */
    protected function unknownAliasException(string $alias, array $knownAliases): \Throwable // @phpstan-ignore typePerfect.narrowReturnObjectType (Segment QueryBuilder child returns QueryException)
    {
        return new DbalException(sprintf(
            'The given alias "%s" is not part of any FROM or JOIN clause table. The currently registered aliases are: %s.',
            $alias,
            implode(', ', $knownAliases)
        ));
    }
}
