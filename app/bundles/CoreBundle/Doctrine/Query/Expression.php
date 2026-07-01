<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Query;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder;

class Expression implements \Countable
{
    private const AND = 'and';
    private const OR  = 'or';

    private CompositeExpression|Composite $expr;

    private bool $firstCall = true;

    private int $count = 0;

    private function __construct(private ExpressionBuilder|Expr $builder, private string $type)
    {
    }

    public static function and(QueryBuilder|DbalQueryBuilder $qb): self
    {
        return new self($qb->expr(), self::AND);
    }

    public static function or(QueryBuilder|DbalQueryBuilder $qb): self
    {
        return new self($qb->expr(), self::OR);
    }

    /**
     * @param CompositeExpression|Expr\Comparison|Expr\Func|Expr\Andx|Expr\Orx|string $part
     * @param CompositeExpression|Expr\Comparison|Expr\Func|Expr\Andx|Expr\Orx|string ...$parts
     */
    public function add($part, ...$parts): self
    {
        $parts = array_merge([$part], $parts);

        $this->count += count($parts);

        if ($this->firstCall) {
            // DBAL path.
            if ($this->builder instanceof ExpressionBuilder) {
                if (self::AND === $this->type) {
                    $this->expr = $this->builder->and(...$parts);
                } else {
                    $this->expr = $this->builder->or(...$parts);
                }
            } else {
                if (self::AND === $this->type) {
                    $this->expr = $this->builder->andX(...$parts);
                } else {
                    $this->expr = $this->builder->orX(...$parts);
                }
            }

            $this->firstCall = false;

            return $this;
        }

        if ($this->expr instanceof CompositeExpression) {
            $this->expr = $this->expr->with(...$parts);
        } else {
            $this->expr = $this->expr->add(...$parts);
        }

        return $this;
    }

    public function expr(): CompositeExpression|Composite
    {
        return $this->expr;
    }

    public function count(): int
    {
        return $this->count;
    }
}
