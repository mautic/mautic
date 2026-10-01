<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

class UntypedReturnRepository
{
    public function findActive()
    {
        return [];
    }

    public function count(): int
    {
        return 0;
    }

    public function __construct()
    {
    }

    private function helper()
    {
        return null;
    }
}
