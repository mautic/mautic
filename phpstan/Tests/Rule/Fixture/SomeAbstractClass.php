<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture;

// class name says "Abstract" but is a concrete class - must be reported
class AbstractParser
{
    public function parse(): void
    {
    }
}
