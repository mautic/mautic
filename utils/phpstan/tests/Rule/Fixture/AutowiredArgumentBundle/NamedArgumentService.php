<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle;

final class NamedArgumentService
{
    public function __construct(
        private Bar $bar,
    ) {
    }
}
