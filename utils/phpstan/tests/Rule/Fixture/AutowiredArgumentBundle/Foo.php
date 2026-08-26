<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle;

final class Foo
{
    public function __construct(
        private Bar $bar,
    ) {
    }
}
