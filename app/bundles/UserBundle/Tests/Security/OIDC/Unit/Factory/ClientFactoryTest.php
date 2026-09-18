<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Factory;

use Mautic\UserBundle\Security\OIDC\DTO\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Event\RegisterScopesEvent;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactory;
use Mautic\UserBundle\Security\OIDC\Service\ClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ClientFactoryTest extends TestCase
{
    public function testBuild(): void
    {
        $parameters      = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $urlGenerator    = self::createMock(UrlGeneratorInterface::class);
        $eventDispatcher = self::createMock(EventDispatcherInterface::class);
        $session         = self::createMock(SessionInterface::class);
        $clientFactory   = new ClientFactory($urlGenerator, $eventDispatcher, $session);

        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::anything())
            ->willReturnCallback(function (RegisterScopesEvent $event) {
                $event->addScopes(['openid', 'email', 'profile']);
            });

        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('open_id_login_check', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/redirect');

        $client = $clientFactory->create($parameters);

        self::assertInstanceOf(ClientInterface::class, $client);
    }
}
