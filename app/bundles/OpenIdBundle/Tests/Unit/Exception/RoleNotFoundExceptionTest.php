<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Exception;

use Mautic\OpenIdBundle\Exception\RoleNotFoundException;
use PHPUnit\Framework\TestCase;

final class RoleNotFoundExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new RoleNotFoundException('Registered user role not found');

        self::assertSame('Registered user role not found', $exception->getMessage());
    }
}
