<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle;

final class NeedsInterface
{
    public function __construct(
        private BarInterface $bar,
    ) {
    }
}
