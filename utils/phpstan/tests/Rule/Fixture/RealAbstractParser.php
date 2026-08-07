<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

abstract class RealAbstractParser
{
    abstract public function parse(): void;
}
