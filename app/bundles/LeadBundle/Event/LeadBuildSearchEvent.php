<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Event\CommonEvent;

class LeadBuildSearchEvent extends CommonEvent
{
    protected string $subQuery = '';

    protected bool $isSearchDone = false;

    protected bool $returnParameters = false;

    protected bool $strict = false;

    protected array $parameters = [];
    public function __construct(
        protected string $string,
        protected string $command,
        protected string $alias,
        protected bool $negate,
        protected QueryBuilder $queryBuilder,
    ) {
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getAlias(): string
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

    public function setString(string $string): void
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
