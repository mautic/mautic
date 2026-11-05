<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\HttpFoundation\Session\Session;

final class SessionConstructor
{
    public function __construct(
        private readonly Session $session,
    ) {
    }
}
