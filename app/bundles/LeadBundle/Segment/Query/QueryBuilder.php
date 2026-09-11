<?php

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Doctrine\Query\QueryBuilder as BaseQueryBuilder;
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


    public function expr(): ExpressionBuilder
    {
        if (null !== $this->_expr) {
            return $this->_expr;
        }

        $this->_expr = new ExpressionBuilder($this->connection);

        return $this->_expr;
    }

    public function setParameter(int|string $key, mixed $value, $type = null): static
    {
        if (is_bool($value)) {
            $value = (int) $value;
        }

        parent::setParameter($key, $value, $type ?? \Doctrine\DBAL\ParameterType::STRING);

        return $this;
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

    public function setParametersPairs($parameters, $filterParameters): static
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
    public function getDebugOutput(): string|array
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
    public function addLogic(string|\Doctrine\DBAL\Query\Expression\CompositeExpression $expression, $glue): void
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

    /**
     * Segment code catches QueryException specifically, so the alias errors keep raising it
     * rather than the base class's generic database exception.
     *
     * @param string[] $knownAliases
     */
    protected function nonUniqueAliasException(string $alias, array $knownAliases): \Throwable
    {
        return QueryException::nonUniqueAlias($alias, $knownAliases);
    }

    /**
     * @param string[] $knownAliases
     */
    protected function unknownAliasException(string $alias, array $knownAliases): \Throwable
    {
        return QueryException::unknownAlias($alias, $knownAliases);
    }
}
