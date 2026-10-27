<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class NullableServiceConstructor
{
    public function __construct(
        private readonly ?SomeModel $someModel,
        private readonly ?SomeAutowireService $someService,
    ) {
    }
}
