<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

// class name says "Abstract" but is a concrete class - must be reported
class AbstractParser
{
    public function parse(): void
    {
    }
}
