<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture;

abstract class RealAbstractParser
{
    abstract public function parse(): void;
}
