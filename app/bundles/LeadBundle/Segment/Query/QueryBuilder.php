<?php

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    private ?\Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder $_expr = null;

    /**
     * Unprocessed logic for segment processing.
     */
    private array $logicStack = [];

    public function __construct(
        private Connection $connection
    ) {
        parent::__construct($connection);
    }

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     * This producer method is intended for convenient inline usage. Example:.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where($qb->expr()->eq('u.id', 1));
     * </code>
     *
     * For more complex expression construction, consider storing the expression
     * builder object in a local variable.
     *
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

        return parent::setParameter($key, $value, $type);
    }

    /**
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
        $this->resetQueryPart('join');
        foreach ($parts['l'] as $key => $part) {
            if ($part['joinAlias'] == $alias) {
                $parts['l'][$key]['joinCondition'] = $expr;
            }
            $this->join($part['joinType'], $part['joinTable'], $part['joinAlias'], $parts['l'][$key]['joinCondition']);
        }

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
        if (null === $connection) {
            $connection = $this->connection;
        }

        return new self($connection);
    }
}
