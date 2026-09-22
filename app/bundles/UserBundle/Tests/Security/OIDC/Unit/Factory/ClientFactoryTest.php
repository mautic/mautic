<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Factory;

use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactory;
use Mautic\UserBundle\Security\OIDC\RegisterScopesEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ClientFactoryTest extends TestCase
{
    public function testBuild(): void
    {
        $parameters      = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $urlGenerator    = $this->createMock(UrlGeneratorInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $session         = $this->createStub(SessionInterface::class);
        $clientFactory   = new ClientFactory($urlGenerator, $eventDispatcher, $session);

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::anything())
            ->willReturnCallback(function (RegisterScopesEvent $event): void {
                $event->addScopes(['openid', 'email', 'profile']);
            });

        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('open_id_login_check', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/redirect');

        $client = $clientFactory->create($parameters);

        $this->assertInstanceOf(ClientInterface::class, $client);
    }
}
