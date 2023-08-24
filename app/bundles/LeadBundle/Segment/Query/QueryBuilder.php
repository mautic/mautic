<?php

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    /**
     * @var ExpressionBuilder
     */
    private $_expr;

    /**
     * Unprocessed logic for segment processing.
     *
     * @var array
     */
    private $logicStack = [];

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

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

    /**
     * Sets a query parameter for the query being constructed.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter(':user_id', 1);
     * </code>
     *
     * @param string|int  $key   the parameter position or name
     * @param mixed       $value the parameter value
     * @param string|null $type  one of the PDO::PARAM_* constants
     *
     * @return $this this QueryBuilder instance
     */
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

        if (null !== $sql && self::STATE_CLEAN === $state) {
            return $sql;
        }

        switch ($this->getType()) {
            case self::INSERT:
                $sql = $this->parentMethod('getSQLForInsert');
                break;
            case self::DELETE:
                $sql = $this->parentMethod('getSQLForDelete');
                break;

            case self::UPDATE:
                $sql = $this->parentMethod('getSQLForUpdate');
                break;

            case self::SELECT:
            default:
                $sql = $this->getSQLForSelect();
                break;
        }

        $state = self::STATE_CLEAN;

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

            $fromClauses[$tableReference] = $tableSql.\Closure::bind(function ($tableReference, &$knownAliases) {
                return $this->{'getSQLForJoins'}($tableReference, $knownAliases);
            }, $this, parent::class)($tableReference, $knownAliases);
        }

        $this->parentMethod('verifyAllAliasesAreKnown', $knownAliases);

        return $fromClauses;
    }

    /**
     * @deprecated this method is not used anywhere and will be removed in the future
     *
     * @return bool
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
        $parts = $this->getQueryPart('join');
        foreach ($parts as $tbl => $joins) {
            foreach ($joins as $key => $join) {
                if ($join['joinAlias'] !== $alias) {
                    continue;
                }

                $parts[$tbl][$key] = array_merge(
                    $join,
                    [
                        'joinType'      => $join['joinType'],
                        'joinTable'     => $join['joinTable'],
                        'joinAlias'     => $join['joinAlias'],
                        'joinCondition' => $join['joinCondition'].' and ('.$expr.')',
                    ]
                );
                $this->add('join', $parts);
                $inserted = true;

                break;
            }
        }

        if (!isset($inserted)) {
            throw new QueryException('Inserting condition to nonexistent join '.$alias);
        }

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
     * @param string      $table
     * @param string|null $joinType allowed values: inner, left, right
     *
     * @return array|bool|string
     */
    public function getTableAlias($table, $joinType = null)
    {
        if (is_null($joinType)) {
            $tables = $this->getTableAliases();

            return isset($tables[$table]) ? $tables[$table] : false;
        }

        $tableJoins = $this->getTableJoins($table);

        foreach ($tableJoins as $tableJoin) {
            if ($tableJoin['joinType'] == $joinType) {
                return $tableJoin['joinAlias'];
            }
        }

        return false;
    }

    public function getTableJoins($tableName)
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
                if (Connection::PARAM_STR_ARRAY === $this->getParameterType($key)) {
                    $val = array_map(static fn ($value) => "'$value'", $val);
                }
                $val = implode(', ', $val);
            }
            $sql = str_replace(":{$key}", $val, $sql);
        }

        return $sql;
    }

    /**
     * @return bool
     */
    public function hasLogicStack()
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

    /**
     * @return array
     */
    public function popLogicStack()
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
    public function addLogic($expression, $glue)
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
        $connection = $connection ?: $this->connection;

        return new self($connection);
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
