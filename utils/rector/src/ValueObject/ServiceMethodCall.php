<?php

declare(strict_types=1);

namespace Utils\Rector\ValueObject;

use PhpParser\Node\Expr\Array_;

final readonly class ServiceMethodCall
{
    public function __construct(
        private string $methodName,
        private Array_ $arguments,
    ) {
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getArguments(): Array_
    {
        return $this->arguments;
    }
}
