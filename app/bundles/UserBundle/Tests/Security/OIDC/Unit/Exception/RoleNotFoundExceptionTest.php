<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Exception;

use Mautic\UserBundle\Security\OIDC\Exception\RoleNotFoundException;
use PHPUnit\Framework\TestCase;

final class RoleNotFoundExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new RoleNotFoundException('Registered user role not found');

        self::assertSame('Registered user role not found', $exception->getMessage());
    }
}
