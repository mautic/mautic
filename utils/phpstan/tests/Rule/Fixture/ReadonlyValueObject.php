<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final readonly class ReadonlyValueObject
{
    public function __construct(
        private int $count,
    ) {
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
