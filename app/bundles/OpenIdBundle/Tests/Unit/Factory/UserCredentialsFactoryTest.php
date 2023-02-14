<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Factory;

use Mautic\OpenIdBundle\Exception\AuthorizationRequestFailedException;
use Mautic\OpenIdBundle\Exception\InvalidMappedIdentifierException;
use Mautic\OpenIdBundle\Exception\UserInfoException;
use Mautic\OpenIdBundle\Factory\UserCredentialsFactory;
use Mautic\OpenIdBundle\Service\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UserCredentialsFactoryTest extends TestCase
{
    public function testBuildThrowsExceptionWhenClientIsNotAuthenticated(): void
    {
        $logger     = self::createMock(LoggerInterface::class);
        $client     = self::createMock(ClientInterface::class);

        $client->expects(self::once())
            ->method('requestUserInfo')
            ->willThrowException(new AuthorizationRequestFailedException('foo'));
        $client->expects(self::atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

        self::expectException(UserInfoException::class);

        $credentialsFactory = new UserCredentialsFactory($client, $logger);
        $credentialsFactory->create();
    }

    public function testBuildThrowsExceptionWhenSubClaimIsNotExtracted(): void
    {
        $logger     = self::createMock(LoggerInterface::class);
        $client     = self::createMock(ClientInterface::class);

        $client->expects(self::once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'email'              => 'email',
                'preferred_username' => 'username',
                'given_name'         => 'givenName',
                'family_name'        => 'familyName',
            ]);
        $client->expects(self::atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

        self::expectException(InvalidMappedIdentifierException::class);

        $credentialsFactory = new UserCredentialsFactory($client, $logger);
        $credentialsFactory->create();
    }

    public function testBuildReturnsCredentials(): void
    {
        $logger     = self::createMock(LoggerInterface::class);
        $client     = self::createMock(ClientInterface::class);

        $client->expects(self::once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'sub'                => 'sub',
                'email'              => 'email',
                'preferred_username' => 'username',
                'given_name'         => 'givenName',
                'family_name'        => 'familyName',
            ]);
        $client->expects(self::atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

        $credentialsFactory = new UserCredentialsFactory($client, $logger);
        $credentials        = $credentialsFactory->create();

        self::assertSame('sub', $credentials->getId());
        self::assertSame('email', $credentials->getEmail());
        self::assertSame('username', $credentials->getPreferredUsername());
        self::assertSame('givenName', $credentials->getGivenName());
        self::assertSame('familyName', $credentials->getFamilyName());
    }

    public function testBuildReturnsCredentialsWhenUserInfoEndpointReturnsData(): void
    {
        $logger     = self::createMock(LoggerInterface::class);
        $client     = self::createMock(ClientInterface::class);

        $client->expects(self::once())
            ->method('getVerifiedClaims')
            ->willReturn([]);
        $client->expects(self::atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');
        $client->expects(self::once())
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

        self::assertSame('sub', $credentials->getId());
        self::assertSame('email', $credentials->getEmail());
        self::assertSame('username', $credentials->getPreferredUsername());
        self::assertSame('givenName', $credentials->getGivenName());
        self::assertSame('familyName', $credentials->getFamilyName());
    }
}
