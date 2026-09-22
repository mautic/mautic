<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\EventListener;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\EventListener\OidcRequestSubscriber;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\OIDC\Settings;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class OidcRequestSubscriberTest extends TestCase
{
    private const LOGIN_PATH            = '/s/login';
    private const DASHBOARD_PATH        = '/s/dashboard';
    private const OPEN_ID_REQUIRED_PATH = '/s/open-id/login-required';
    private const OPEN_ID_FIREWALL      = 'security.firewall.map.context.open_id';
    private const MAIN_FIREWALL         = 'security.firewall.map.context.main';
    private const LOGIN_FIREWALL        = 'security.firewall.map.context.login';

    public function testOnKernelRequestRedirectsFromOpenIdLoginRequiredWhenDisabled(): void
    {
        $settings     = $this->createSettings(false, true);
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $logger       = $this->createStub(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::OPEN_ID_FIREWALL, self::LOGIN_PATH);

        $subscriber = new OidcRequestSubscriber($settings, $tokenStorage, $router, $logger);
        $subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestRedirectsFromOpenIdLoginRequiredWhenLoggedInWIthOpenId(): void
    {
        $settings     = $this->createSettings(true, true);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $logger       = $this->createStub(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::OPEN_ID_FIREWALL, self::DASHBOARD_PATH);

        $token = $this->createOpenIdPluginToken();
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $subscriber = new OidcRequestSubscriber($settings, $tokenStorage, $router, $logger);
        $subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestRedirectsFromOpenIdLoginRequiredWhenLoggedInWIthForm(): void
    {
        $settings     = $this->createSettings(true, false);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $logger       = $this->createStub(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::OPEN_ID_FIREWALL, self::DASHBOARD_PATH);

        $token = $this->createFormPluginToken();
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $subscriber = new OidcRequestSubscriber($settings, $tokenStorage, $router, $logger);
        $subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestDoesNotRedirectFromSecuredAreaIfOpenIdIsNotRequired(): void
    {
        $settings     = $this->createSettings(true, false);
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $logger       = $this->createStub(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::MAIN_FIREWALL);

        $subscriber = new OidcRequestSubscriber($settings, $tokenStorage, $router, $logger);
        $subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestDoesNotRedirectFromSecuredAreaIfOpenIdIsNotEnabled(): void
    {
        $settings     = $this->createSettings(false, true);
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $logger       = $this->createStub(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::MAIN_FIREWALL);

        $subscriber = new OidcRequestSubscriber($settings, $tokenStorage, $router, $logger);
        $subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestRedirectFromSecuredAreaIfNotLoggedInWithOpenId(): void
    {
        $settings     = $this->createSettings(true, true);
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $logger       = $this->createStub(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::MAIN_FIREWALL, self::OPEN_ID_REQUIRED_PATH);

        $subscriber = new OidcRequestSubscriber($settings, $tokenStorage, $router, $logger);
        $subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestDontRedirectFromLoginIfNotLoggedInWithOpenId(): void
    {
        $settings     = $this->createSettings(true, true);
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $logger       = $this->createStub(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::LOGIN_FIREWALL);

        $subscriber = new OidcRequestSubscriber($settings, $tokenStorage, $router, $logger);
        $subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestRedirectFromLoginIfLoggedInWithOpenId(): void
    {
        $settings     = $this->createSettings(true, true);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $logger       = $this->createStub(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::LOGIN_FIREWALL, self::DASHBOARD_PATH);

        $token = $this->createOpenIdPluginToken();
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $subscriber = new OidcRequestSubscriber($settings, $tokenStorage, $router, $logger);
        $subscriber->onKernelRequest($requestEvent);
    }

    private function createSettings(bool $enabled, bool $required): Settings
    {
        return new Settings($enabled, $required, false, null);
    }

    private function createOpenIdPluginToken(): PluginToken
    {
        $token = $this->createMock(PluginToken::class);
        $token->method('getUser')->willReturn(new User());
        $token->method('getProviderKey')->willReturn('open_id');
        $token->method('isSupportUser')->willReturn(false);
        $token->method('hasAttribute')->with('_firewall_name')->willReturn(true);
        $token->method('getAttribute')->with('_firewall_name')->willReturn('open_id');

        return $token;
    }

    private function createFormPluginToken(): PluginToken
    {
        $token = $this->createMock(PluginToken::class);
        $token->method('getUser')->willReturn(new User());
        $token->method('getProviderKey')->willReturn('main');
        $token->method('isSupportUser')->willReturn(false);
        $token->method('hasAttribute')->with('_firewall_name')->willReturn(true);
        $token->method('getAttribute')->with('_firewall_name')->willReturn('main');

        return $token;
    }

    private function getUrlGenerator(): UrlGeneratorInterface
    {
        $router = $this->createMock(UrlGeneratorInterface::class);

        $router->expects($this->atLeastOnce())
            ->method('generate')
            ->willReturnCallback(function (string $route): string {
                return match ($route) {
                    'open_id_login_required' => self::OPEN_ID_REQUIRED_PATH,
                    'login' => self::LOGIN_PATH,
                    'mautic_dashboard_index' => self::DASHBOARD_PATH,
                    default => throw new \RuntimeException("Unexpected route: $route"),
                };
            });

        return $router;
    }

    private function getRequestEvent(string $firewall, ?string $targetUrl = null): RequestEvent
    {
        $request      = new Request([], [], ['_firewall_context' => $firewall]);
        $requestEvent = $this->createMock(RequestEvent::class);

        $requestEvent
            ->method('getRequest')
            ->willReturn($request);

        if ($targetUrl) {
            $requestEvent->expects($this->once())
                ->method('setResponse')
                ->with(self::callback(static fn($response): bool => $response->getTargetUrl() === $targetUrl));
        } else {
            $requestEvent->expects($this->never())
                ->method('setResponse');
        }

        return $requestEvent;
    }
}
