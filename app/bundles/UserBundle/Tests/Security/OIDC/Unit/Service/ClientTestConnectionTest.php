<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Service;

use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Mautic\UserBundle\Security\OIDC\Client\Client;
use Mautic\UserBundle\Security\OIDC\Client\ClientBridgeInterface;
use PHPUnit\Framework\TestCase;

final class ClientTestConnectionTest extends TestCase
{
    public function testConnectionReturnsNullWhenAlreadyAuthenticated(): void
    {
        $openIdClient = $this->createMock(ClientBridgeInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(true);

        $client = new Client($openIdClient, 'sub');

        $result = $client->testConnection();

        $this->assertNull($result);
    }

    public function testConnectionDelegatesToBridge(): void
    {
        $openIdClient = $this->createMock(ClientBridgeInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(false);
        $openIdClient->expects($this->once())
            ->method('testConnection')
            ->willReturn(null);

        $client = new Client($openIdClient, 'sub');

        $result = $client->testConnection();

        $this->assertNull($result);
    }

    public function testConnectionReturnsErrorMessageFromBridge(): void
    {
        $openIdClient = $this->createMock(ClientBridgeInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(false);
        $openIdClient->expects($this->once())
            ->method('testConnection')
            ->willReturn('Connection to OIDC provider failed');

        $client = new Client($openIdClient, 'sub');

        $result = $client->testConnection();

        $this->assertSame('Connection to OIDC provider failed', $result);
    }

    public function testConnectionReturnsErrorMessageOnException(): void
    {
        $openIdClient = $this->createMock(ClientBridgeInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(false);
        $openIdClient->expects($this->once())
            ->method('testConnection')
            ->willThrowException(new OidcAuthorizationException('Network error'));

        $client = new Client($openIdClient, 'sub');

        $result = $client->testConnection();

        $this->assertSame('Network error', $result);
    }
}
