<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

function createAnonymousWithNullableService(): object
{
    return new class(null) {
        public function __construct(
            private readonly ?SomeModel $someModel,
        ) {
        }
    };
}
