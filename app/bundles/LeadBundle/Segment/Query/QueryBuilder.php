<?php

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    private ?ExpressionBuilder $_expr = null;

    /**
     * Unprocessed logic for segment processing.
     */
    private array $logicStack = [];

    public function __construct(
        private Connection $connection,
    ) {
        parent::__construct($connection);
    }

    /**
     * @return ExpressionBuilder
     */
    public function expr()
    {
        if (!is_null($this->_expr)) {
            return $this->_expr;
        }

        $this->_expr = new ExpressionBuilder($this->connection);

        return $this->_expr;
    }

    public function setParameter($key, $value, $type = null)
    {
        if (str_starts_with($key, ':')) {
            // For consistency sake, remove the :
            $key = substr($key, 1);
            @\trigger_error('Using query key with ":" is deprecated. Use key without ":" instead.', \E_USER_DEPRECATED);
        }

        if (is_bool($value)) {
            $value = (int) $value;
        }

        return parent::setParameter($key, $value, $type);
    }

    /**
     * @param string $queryPartName
     * @param mixed  $value
     *
     * @return $this
     */
    public function setQueryPart($queryPartName, $value)
    {
        $this->resetQueryPart($queryPartName);
        $this->add($queryPartName, $value);

        return $this;
    }

    public function getSQL()
    {
        $sql   = &$this->parentProperty('sql');
        $state = &$this->parentProperty('state');

        if (null !== $sql && 1 /* self::STATE_CLEAN */ === $state) {
            return $sql;
        }

        $sql = match ($this->getType()) { /** @phpstan-ignore-line this method is deprecated. We'll have to find a way how to refactor this method. */
            3 /* self::INSERT */ => $this->parentMethod('getSQLForInsert'),
            1 /* self::DELETE */ => $this->parentMethod('getSQLForDelete'),
            2 /* self::UPDATE */ => $this->parentMethod('getSQLForUpdate'),
            default              => $this->getSQLForSelect(),
        };

        $state = 1 /* self::STATE_CLEAN */;

        return $sql;
    }

    private function getSQLForSelect(): string
    {
        $sqlParts = $this->getQueryParts();

        $query = 'SELECT '.($sqlParts['distinct'] ? 'DISTINCT ' : '').
            implode(', ', $sqlParts['select']);

        $query .= ($sqlParts['from'] ? ' FROM '.implode(', ', $this->getFromClauses()) : '')
            .(null !== $sqlParts['where'] ? ' WHERE '.($sqlParts['where']) : '')
            .($sqlParts['groupBy'] ? ' GROUP BY '.implode(', ', $sqlParts['groupBy']) : '')
            .(null !== $sqlParts['having'] ? ' HAVING '.($sqlParts['having']) : '')
            .($sqlParts['orderBy'] ? ' ORDER BY '.implode(', ', $sqlParts['orderBy']) : '');

        if ($this->parentMethod('isLimitQuery')) {
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
        foreach ($this->getQueryParts()['from'] as $from) {
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

            $fromClauses[$tableReference] = $tableSql.\Closure::bind(
                fn ($tableReference, &$knownAliases) => $this->{'getSQLForJoins'}($tableReference, $knownAliases),
                $this,
                parent::class
            )($tableReference, $knownAliases);
        }

        $this->parentMethod('verifyAllAliasesAreKnown', $knownAliases);

        return $fromClauses;
    }

    /**
     * @param string $alias
     *
     * @return string|false
     */
    public function getJoinCondition($alias)
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
     * @return $this
     *
     * @throws QueryException
     */
    public function addJoinCondition($alias, $expr)
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

    /**
     * @return $this
     */
    public function replaceJoinCondition($alias, $expr)
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

    /**
     * @return array|bool|string
     */
    public function getTableAlias(string $table, $joinType = null)
    {
        if (is_null($joinType)) {
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
     *
     * @return string
     */
    public function guessPrimaryLeadContactIdColumn()
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
        $tables     = array_reduce($queryParts['from'], function ($result, $item) {
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
                $val = "'$val'";
            } elseif (is_array($val)) {
                if (ArrayParameterType::STRING === $this->getParameterType($key)) {
                    $val = array_map(static fn ($value) => "'$value'", $val);
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
     * @return array
     */
    public function getLogicStack()
    {
        return $this->logicStack;
    }

    public function popLogicStack(): array
    {
        $stack            = $this->logicStack;
        $this->logicStack = [];

        return $stack;
    }

    /**
     * @return $this
     */
    private function addLogicStack($expression)
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
        if ('or' == $glue) {
            //  Is this the first condition in query builder?
            if (!is_null($this->getQueryPart('where'))) {
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
     *
     * @return $this
     */
    public function applyStackLogic()
    {
        if ($this->hasLogicStack()) {
            $stackGroupExpression = new CompositeExpression(CompositeExpression::TYPE_AND, $this->popLogicStack());
            $this->orWhere($stackGroupExpression);
        }

        return $this;
    }

    public function createQueryBuilder(Connection $connection = null): QueryBuilder
    {
        return new self($connection ?: $this->connection);
    }

    /**
     * @return mixed
     *
     * @noinspection PhpPassByRefInspection
     */
    private function &parentProperty(string $property)
    {
        return \Closure::bind(function &() use ($property) {
            return $this->$property;
        }, $this, parent::class)();
    }

    /**
     * @param mixed ...$arguments
     *
     * @return mixed
     */
    private function parentMethod(string $method, ...$arguments)
    {
        return \Closure::bind(function () use ($method, $arguments) {
            return $this->$method(...$arguments);
        }, $this, parent::class)();
    }
}
