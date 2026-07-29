<?php

declare(strict_types=1);

namespace Utils\Rector\ValueObject;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;

final readonly class ServiceTag
{
    /**
     * @param Expr $name a plain name or a class constant like FixturesCompilerPass::FIXTURE_TAG
     */
    public function __construct(
        private Expr $name,
        private Array_ $arguments,
    ) {
    }

    public function getName(): Expr
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
