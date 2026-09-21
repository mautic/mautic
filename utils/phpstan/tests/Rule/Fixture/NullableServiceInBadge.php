<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final class NullableServiceInBadge implements BadgeInterface
{
    public function __construct(
        private readonly ?SomeAutowireService $someService,
    ) {
    }

    public function isResolved(): bool
    {
        return true;
    }
}
