<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\DTO;

use Mautic\OpenIdBundle\DTO\UserCredentials;
use PHPUnit\Framework\TestCase;

final class UserCredentialsTest extends TestCase
{
    public function testCredentialsDefaults(): void
    {
        $credentials = new UserCredentials('id');

        $this->assertSame('id', $credentials->getId());
        $this->assertNull($credentials->getEmail());
        $this->assertNull($credentials->getPreferredUsername());
        $this->assertNull($credentials->getGivenName());
        $this->assertNull($credentials->getFamilyName());
    }

    public function testCredentialsNullableFields(): void
    {
        $credentials = new UserCredentials('sub', null, null, null, null);

        self::assertSame('sub', $credentials->getId());
        self::assertNull($credentials->getEmail());
        self::assertNull($credentials->getPreferredUsername());
        self::assertNull($credentials->getGivenName());
        self::assertNull($credentials->getFamilyName());
    }

    public function testCredentialsGetters(): void
    {
        $credentials = new UserCredentials('subject', 'email', 'preferredName', 'firstName', 'lastName');

        self::assertSame('subject', $credentials->getId());
        self::assertSame('email', $credentials->getEmail());
        self::assertSame('preferredName', $credentials->getPreferredUsername());
        self::assertSame('firstName', $credentials->getGivenName());
        self::assertSame('lastName', $credentials->getFamilyName());
    }
}
