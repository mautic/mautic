<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\DTO;

use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
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
        $credentials = new UserCredentials('sub');

        $this->assertSame('sub', $credentials->getId());
        $this->assertNull($credentials->getEmail());
        $this->assertNull($credentials->getPreferredUsername());
        $this->assertNull($credentials->getGivenName());
        $this->assertNull($credentials->getFamilyName());
    }

    public function testCredentialsGetters(): void
    {
        $credentials = new UserCredentials('subject', 'email', 'preferredName', 'firstName', 'lastName');

        $this->assertSame('subject', $credentials->getId());
        $this->assertSame('email', $credentials->getEmail());
        $this->assertSame('preferredName', $credentials->getPreferredUsername());
        $this->assertSame('firstName', $credentials->getGivenName());
        $this->assertSame('lastName', $credentials->getFamilyName());
    }
}
