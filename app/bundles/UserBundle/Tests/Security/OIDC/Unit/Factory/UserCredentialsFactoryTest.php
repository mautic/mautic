<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Factory;

use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Security\OIDC\Factory\UserCredentialsFactory;
use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UserCredentialsFactoryTest extends TestCase
{
    public function testBuildThrowsExceptionWhenClientIsNotAuthenticated(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('requestUserInfo')
            ->willThrowException(new OidcAuthorizationException('foo'));
        $client->expects($this->atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

        $this->expectException(OidcException::class);

        $credentialsFactory = new UserCredentialsFactory($client, $logger);
        $credentialsFactory->create();
    }

    public function testBuildThrowsExceptionWhenSubClaimIsNotExtracted(): void
    {
        $logger     = $this->createStub(LoggerInterface::class);
        $client     = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'email'              => 'email',
                'preferred_username' => 'username',
                'given_name'         => 'givenName',
                'family_name'        => 'familyName',
            ]);
        $client->expects($this->atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

        $this->expectException(OidcException::class);

        $credentialsFactory = new UserCredentialsFactory($client, $logger);
        $credentialsFactory->create();
    }

    public function testBuildReturnsCredentials(): void
    {
        $logger     = $this->createStub(LoggerInterface::class);
        $client     = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'sub'                => 'sub',
                'email'              => 'email',
                'preferred_username' => 'username',
                'given_name'         => 'givenName',
                'family_name'        => 'familyName',
            ]);
        $client->expects($this->atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

        $credentialsFactory = new UserCredentialsFactory($client, $logger);
        $credentials        = $credentialsFactory->create();

        $this->assertSame('sub', $credentials->getId());
        $this->assertSame('email', $credentials->getEmail());
        $this->assertSame('username', $credentials->getPreferredUsername());
        $this->assertSame('givenName', $credentials->getGivenName());
        $this->assertSame('familyName', $credentials->getFamilyName());
    }

    public function testBuildReturnsCredentialsWhenUserInfoEndpointReturnsData(): void
    {
        $logger     = $this->createStub(LoggerInterface::class);
        $client     = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('getVerifiedClaims')
            ->willReturn([]);
        $client->expects($this->atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');
        $client->expects($this->once())
            ->method('requestUserInfo')
            ->willReturn([
                'sub'                => 'sub',
                'email'              => 'email',
                'preferred_username' => 'username',
                'given_name'         => 'givenName',
                'family_name'        => 'familyName',
            ]);

        $credentialsFactory = new UserCredentialsFactory($client, $logger);
        $credentials        = $credentialsFactory->create();

        $this->assertSame('sub', $credentials->getId());
        $this->assertSame('email', $credentials->getEmail());
        $this->assertSame('username', $credentials->getPreferredUsername());
        $this->assertSame('givenName', $credentials->getGivenName());
        $this->assertSame('familyName', $credentials->getFamilyName());
    }
}
