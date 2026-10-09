<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Factory;

use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactoryInterface;
use Mautic\UserBundle\Security\OIDC\Factory\UserCredentialsFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UserCredentialsFactoryTest extends TestCase
{
    public function testBuildThrowsExceptionWhenClientIsNotAuthenticated(): void
    {
        $clientFactory = $this->createMock(ClientFactoryInterface::class);
        $client        = $this->createMock(ClientInterface::class);

        $client->expects($this->once())->method('requestUserInfo')->willThrowException(new OidcAuthorizationException('foo'));
        $client->expects($this->once())->method('getVerifiedClaims')->willThrowException(new OidcAuthorizationException('bar'));
        $client->expects($this->atLeastOnce())->method('getMappingField')->willReturn('sub');
        $clientFactory->expects($this->once())->method('create')->willReturn($client);

        $this->expectException(OidcException::class);
        $this->expectExceptionMessage('mautic.open_id.login.exception.user_info');

        $credentialsFactory = new UserCredentialsFactory($clientFactory, new ClientCredentials(), $this->createStub(LoggerInterface::class));
        $credentialsFactory->create();
    }

    public function testBuildThrowsExceptionWhenSubClaimIsNotExtracted(): void
    {
        $clientFactory = $this->createMock(ClientFactoryInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'email'              => 'email',
                'preferred_username' => 'username',
                'given_name'         => 'givenName',
                'family_name'        => 'familyName',
            ]);
        $client->expects($this->atLeastOnce())->method('getMappingField')->willReturn('sub');
        $clientFactory->expects($this->once())->method('create')->willReturn($client);

        $this->expectException(OidcException::class);
        $this->expectExceptionMessage('mautic.open_id.login.exception.invalid_mapping_field');

        $credentialsFactory = new UserCredentialsFactory($clientFactory, new ClientCredentials(), $this->createStub(LoggerInterface::class));
        $credentialsFactory->create();
    }

    public function testBuildReturnsCredentials(): void
    {
        $clientFactory = $this->createMock(ClientFactoryInterface::class);
        $client        = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'sub'                => 'sub',
                'email'              => 'email',
                'preferred_username' => 'username',
                'given_name'         => 'givenName',
                'family_name'        => 'familyName',
            ]);
        $client->expects($this->atLeastOnce())->method('getMappingField')->willReturn('sub');
        $clientFactory->expects($this->once())->method('create')->willReturn($client);

        $credentialsFactory = new UserCredentialsFactory($clientFactory, new ClientCredentials(), $this->createStub(LoggerInterface::class));
        $credentials        = $credentialsFactory->create();

        $this->assertSame('sub', $credentials->getId());
        $this->assertSame('email', $credentials->getEmail());
        $this->assertSame('username', $credentials->getPreferredUsername());
        $this->assertSame('givenName', $credentials->getGivenName());
        $this->assertSame('familyName', $credentials->getFamilyName());
    }

    public function testBuildReturnsCredentialsWhenUserInfoEndpointReturnsData(): void
    {
        $clientFactory = $this->createMock(ClientFactoryInterface::class);
        $client        = $this->createMock(ClientInterface::class);
        $clientFactory->expects($this->once())->method('create')->willReturn($client);

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

        $credentialsFactory = new UserCredentialsFactory($clientFactory, new ClientCredentials(), $this->createStub(LoggerInterface::class));
        $credentials        = $credentialsFactory->create();

        $this->assertSame('sub', $credentials->getId());
        $this->assertSame('email', $credentials->getEmail());
        $this->assertSame('username', $credentials->getPreferredUsername());
        $this->assertSame('givenName', $credentials->getGivenName());
        $this->assertSame('familyName', $credentials->getFamilyName());
    }
}
