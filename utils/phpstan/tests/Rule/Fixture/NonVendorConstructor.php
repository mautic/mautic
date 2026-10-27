<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class NonVendorConstructor
{
    public function __construct(
        private readonly NonVendor $nonVendor,
    ) {
    }
}
