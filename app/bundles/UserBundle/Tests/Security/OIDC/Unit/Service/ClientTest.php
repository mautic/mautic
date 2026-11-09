<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Service;

use Mautic\UserBundle\Security\OIDC\Client\Client;
use Mautic\UserBundle\Security\OIDC\Client\ClientBridgeInterface;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testGetMappingField(): void
    {
        $openIdClient = $this->createStub(ClientBridgeInterface::class);
        $mappingField = 'email';
        $client       = new Client($openIdClient, $mappingField);

        $this->assertSame($mappingField, $client->getMappingField());
    }

    public function testAuthenticateIsTrue(): void
    {
        $openIdClient = $this->createMock(ClientBridgeInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(true);

        $client = new Client($openIdClient, 'sub');

        $this->assertTrue($client->isAuthenticated());
    }

    public function testAuthenticateIsFalse(): void
    {
        $openIdClient = $this->createMock(ClientBridgeInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(false);

        $client = new Client($openIdClient, 'sub');

        $this->assertFalse($client->isAuthenticated());
    }

    public function testGetVerifiedClaims(): void
    {
        $claims       = ['openid', 'email'];
        $openIdClient = $this->createMock(ClientBridgeInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(true);
        $openIdClient->expects($this->exactly(2))
            ->method('getVerifiedClaims')
            ->willReturnOnConsecutiveCalls('openid', 'email');

        $client         = new Client($openIdClient, 'sub');
        $verifiedClaims = $client->getVerifiedClaims($claims);

        $this->assertSame(['openid' => 'openid', 'email' => 'email'], $verifiedClaims);
    }

    public function testRequestUserInfo(): void
    {
        $claims       = ['openid', 'email'];
        $openIdClient = $this->createMock(ClientBridgeInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(true);
        $openIdClient->expects($this->exactly(2))
            ->method('requestUserInfo')
            ->willReturnOnConsecutiveCalls('openid', 'email');

        $client         = new Client($openIdClient, 'sub');
        $verifiedClaims = $client->requestUserInfo($claims);

        $this->assertSame(['openid' => 'openid', 'email' => 'email'], $verifiedClaims);
    }
}
