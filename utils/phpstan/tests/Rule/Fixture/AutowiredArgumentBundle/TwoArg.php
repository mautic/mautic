<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle;

final class TwoArg
{
    public function __construct(
        private Bar $bar,
        private Baz $baz,
    ) {
    }
}
