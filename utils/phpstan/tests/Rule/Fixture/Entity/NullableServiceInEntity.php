<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\Entity;

use Utils\PHPStan\Tests\Rule\Fixture\SomeAutowireService;

final class NullableServiceInEntity
{
    public function __construct(
        private readonly ?SomeAutowireService $someService,
    ) {
    }
}
