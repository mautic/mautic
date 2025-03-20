<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

class SearchQueryEvent extends Event
{
    use SearchEventTrait;

    private Base|Comparison $expr;

    /**
     * @var mixed[]
     */
    private array $parameters;

    public function __construct(
        private object $filter,
        private QueryBuilder $query,
        private string $alias,
        private string $context,
    ) {
    }

    public function getFilter(): object
    {
        return $this->filter;
    }

    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getExpr(): Base|Comparison
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
