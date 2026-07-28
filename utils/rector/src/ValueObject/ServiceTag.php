<?php

declare(strict_types=1);

namespace Utils\Rector\ValueObject;

use PhpParser\Node\Expr\Array_;

final readonly class ServiceTag
{
    public function __construct(
        private string $name,
        private Array_ $arguments,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
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
