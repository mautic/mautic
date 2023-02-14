<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Service;

use Mautic\OpenIdBundle\Service\Client;
use Mautic\OpenIdBundle\Service\ClientBridgeInterface;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testGetMappingField(): void
    {
        $openIdClient = self::createMock(ClientBridgeInterface::class);
        $mappingField = 'email';
        $client       = new Client($openIdClient, $mappingField);

        $this->assertSame($mappingField, $client->getMappingField());
    }

    public function testAuthenticateIsTrue(): void
    {
        $openIdClient = self::createMock(ClientBridgeInterface::class);
        $openIdClient->expects(self::once())
            ->method('authenticate')
            ->willReturn(true);

        $client = new Client($openIdClient, 'sub');

        self::assertTrue($client->isAuthenticated());
    }

    public function testAuthenticateIsFalse(): void
    {
        $openIdClient = self::createMock(ClientBridgeInterface::class);
        $openIdClient->expects(self::once())
            ->method('authenticate')
            ->willReturn(false);

        $client = new Client($openIdClient, 'sub');

        self::assertFalse($client->isAuthenticated());
    }

    public function testGetVerifiedClaims(): void
    {
        $claims       = ['openid', 'email'];
        $openIdClient = self::createMock(ClientBridgeInterface::class);
        $openIdClient->expects(self::once())
            ->method('authenticate')
            ->willReturn(true);
        $openIdClient->expects(self::exactly(2))
            ->method('getVerifiedClaims')
            ->willReturnOnConsecutiveCalls('openid', 'email');

        $client         = new Client($openIdClient, 'sub');
        $verifiedClaims = $client->getVerifiedClaims($claims);

        self::assertSame(['openid' => 'openid', 'email' => 'email'], $verifiedClaims);
    }

    public function testRequestUserInfo(): void
    {
        $claims       = ['openid', 'email'];
        $openIdClient = self::createMock(ClientBridgeInterface::class);
        $openIdClient->expects(self::once())
            ->method('authenticate')
            ->willReturn(true);
        $openIdClient->expects(self::exactly(2))
            ->method('requestUserInfo')
            ->willReturnOnConsecutiveCalls('openid', 'email');

        $client         = new Client($openIdClient, 'sub');
        $verifiedClaims = $client->requestUserInfo($claims);

        self::assertSame(['openid' => 'openid', 'email' => 'email'], $verifiedClaims);
    }
}
