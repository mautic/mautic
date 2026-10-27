<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\HttpFoundation\RequestStack;

final class ClassWithoutInterfaceConstructor
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SomeModel $someModel,
    ) {
    }
}
