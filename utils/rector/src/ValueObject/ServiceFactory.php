<?php

declare(strict_types=1);

namespace Utils\Rector\ValueObject;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;

final readonly class ServiceFactory
{
    /**
     * @param Expr   $value     the ->factory() value, e.g. [service('doctrine'), 'getManagerForClass']
     * @param Array_ $arguments the ->args() the factory is called with
     */
    public function __construct(
        private Expr $value,
        private Array_ $arguments,
    ) {
    }

    public function getValue(): Expr
    {
        return $this->value;
    }

    public function getArguments(): Array_
    {
        return $this->arguments;
    }

    public function hasArguments(): bool
    {
        return [] !== $this->arguments->items;
    }
}
