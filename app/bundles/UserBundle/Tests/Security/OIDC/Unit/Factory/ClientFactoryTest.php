<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Factory;

use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactory;
use Mautic\UserBundle\Security\OIDC\RegisterScopesEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
        $requestStack    = $this->createMock(RequestStack::class);

        $requestStack->expects($this->once())->method('getSession')->willReturn($session);

        $clientFactory   = new ClientFactory($urlGenerator, $eventDispatcher, $requestStack);

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::anything())
            ->willReturnCallback(function (RegisterScopesEvent $event): object {
                $event->addScopes(['openid', 'email', 'profile']);

                return $event;
            });

        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('mautic_oidc_check', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/redirect');

        $clientFactory->create($parameters);
    }
}
