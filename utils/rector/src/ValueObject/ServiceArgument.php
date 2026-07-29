<?php

declare(strict_types=1);

namespace Utils\Rector\ValueObject;

use PhpParser\Node\Expr;

final readonly class ServiceArgument
{
    /**
     * @param string $name constructor parameter name, including the leading "$"
     */
    public function __construct(
        private string $name,
        private Expr $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): Expr
    {
        return $this->value;
    }
}
