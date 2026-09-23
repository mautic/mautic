<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;

final class SearchQueryEvent extends AbstractSearchEvent
{
    private Base|Comparison|null $expr = null;

    /**
     * @var mixed[]
     */
    private array $parameters = [];

    public function __construct(
        private readonly object $filter,
        private readonly ORMQueryBuilder|DBALQueryBuilder $query,
        private readonly string $alias,
        protected string $context,
    ) {
    }

    public function getFilter(): object
    {
        return $this->filter;
    }

    public function getQuery(): ORMQueryBuilder|DBALQueryBuilder
    {
        return $this->query;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getExpr(): Base|Comparison|null
    {
        return $this->expr;
    }

    public function setExpr(Base|Comparison $expr): void
    {
        $this->expr = $expr;
    }

    /**
     * @return mixed[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param mixed[] $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
