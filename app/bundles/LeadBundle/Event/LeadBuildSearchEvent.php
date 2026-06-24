<?php

namespace Mautic\LeadBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Event\CommonEvent;

class LeadBuildSearchEvent extends CommonEvent
{
    protected string $subQuery;

    protected bool $isSearchDone;

    protected bool $returnParameters;

    protected bool $strict;

    protected array $parameters;

    /**
     * @param string $string
     * @param string $command
     * @param string $alias
     */
    public function __construct(
        protected $string,
        protected $command,
        protected $alias,
        protected bool $negate,
        protected QueryBuilder $queryBuilder,
    ) {
        $this->subQuery         = '';
        $this->isSearchDone     = false;
        $this->strict           = false;
        $this->returnParameters = false;
        $this->parameters       = [];
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    public function isNegation(): bool
    {
        return $this->negate;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setSearchStatus(bool $status): void
    {
        $this->isSearchDone = $status;
    }

    public function setSubQuery(string $query): void
    {
        $this->subQuery = $query;

        $this->setSearchStatus(true);
    }

    public function isSearchDone(): bool
    {
        return $this->isSearchDone;
    }

    public function getSubQuery(): string
    {
        return $this->subQuery;
    }

    /**
     * @param array $string
     */
    public function setString($string): void
    {
        $this->string = $string;
    }

    public function getStrict(): bool
    {
        return $this->strict;
    }

    public function setStrict(bool $val): void
    {
        $this->strict = $val;
    }

    public function getReturnParameters(): bool
    {
        return $this->returnParameters;
    }

    public function setReturnParameters(bool $val): void
    {
        $this->returnParameters = $val;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $val): void
    {
        $this->parameters = $val;
    }
}
