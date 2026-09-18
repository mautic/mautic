<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\EventListener;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\EventListener\KernelRequestSubscriber;
use Mautic\UserBundle\Security\OIDC\Tests\Builder\DTO\ParametersBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

final class KernelRequestSubscriberTest extends TestCase
{
    private const LOGIN_PATH            = '/s/login';
    private const DASHBOARD_PATH        = '/s/dashboard';
    private const OPEN_ID_REQUIRED_PATH = '/s/open-id/login-required';
    private const OPEN_ID_FIREWALL      = 'security.firewall.map.context.open_id';
    private const MAIN_FIREWALL         = 'security.firewall.map.context.main';
    private const LOGIN_FIREWALL        = 'security.firewall.map.context.login';

    public function testOnKernelRequestRedirectsFromOpenIdLoginRequiredWhenDisabled(): void
    {
        $parameters   = (new ParametersBuilder())->withIsEnabled(false)->build();
        $tokenStorage = self::createMock(TokenStorageInterface::class);
        $logger       = self::createMock(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::OPEN_ID_FIREWALL, self::LOGIN_PATH);

        $kernelRequestListener = new KernelRequestSubscriber($parameters, $tokenStorage, $router, $logger);
        $kernelRequestListener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestRedirectsFromOpenIdLoginRequiredWhenLoggedInWIthOpenId(): void
    {
        $parameters   = (new ParametersBuilder())->build();
        $tokenStorage = self::createMock(TokenStorageInterface::class);
        $logger       = self::createMock(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::OPEN_ID_FIREWALL, self::DASHBOARD_PATH);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(new PostAuthenticationGuardToken(new User(), 'open_id', []));

        $kernelRequestListener = new KernelRequestSubscriber($parameters, $tokenStorage, $router, $logger);
        $kernelRequestListener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestRedirectsFromOpenIdLoginRequiredWhenLoggedInWIthForm(): void
    {
        $parameters   = (new ParametersBuilder())->withIsRequired(false)->build();
        $tokenStorage = self::createMock(TokenStorageInterface::class);
        $logger       = self::createMock(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::OPEN_ID_FIREWALL, self::DASHBOARD_PATH);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(new UsernamePasswordToken(new User(), 'password', 'form'));

        $kernelRequestListener = new KernelRequestSubscriber($parameters, $tokenStorage, $router, $logger);
        $kernelRequestListener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestDoesNotRedirectFromSecuredAreaIfOpenIdIsNotRequired(): void
    {
        $parameters   = (new ParametersBuilder())->withIsRequired(false)->build();
        $tokenStorage = self::createMock(TokenStorageInterface::class);
        $logger       = self::createMock(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::MAIN_FIREWALL);

        $kernelRequestListener = new KernelRequestSubscriber($parameters, $tokenStorage, $router, $logger);
        $kernelRequestListener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestDoesNotRedirectFromSecuredAreaIfOpenIdIsNotEnabled(): void
    {
        $parameters   = (new ParametersBuilder())->withIsEnabled(false)->build();
        $tokenStorage = self::createMock(TokenStorageInterface::class);
        $logger       = self::createMock(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::MAIN_FIREWALL);

        $kernelRequestListener = new KernelRequestSubscriber($parameters, $tokenStorage, $router, $logger);
        $kernelRequestListener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestRedirectFromSecuredAreaIfNotLoggedInWithOpenId(): void
    {
        $parameters   = (new ParametersBuilder())->build();
        $tokenStorage = self::createMock(TokenStorageInterface::class);
        $logger       = self::createMock(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::MAIN_FIREWALL, self::OPEN_ID_REQUIRED_PATH);

        $kernelRequestListener = new KernelRequestSubscriber($parameters, $tokenStorage, $router, $logger);
        $kernelRequestListener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestDontRedirectFromLoginIfNotLoggedInWithOpenId(): void
    {
        $parameters   = (new ParametersBuilder())->build();
        $tokenStorage = self::createMock(TokenStorageInterface::class);
        $logger       = self::createMock(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::LOGIN_FIREWALL);

        $kernelRequestListener = new KernelRequestSubscriber($parameters, $tokenStorage, $router, $logger);
        $kernelRequestListener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestRedirectFromLoginIfLoggedInWithOpenId(): void
    {
        $parameters   = (new ParametersBuilder())->build();
        $tokenStorage = self::createMock(TokenStorageInterface::class);
        $logger       = self::createMock(LoggerInterface::class);
        $router       = $this->getUrlGenerator();
        $requestEvent = $this->getRequestEvent(self::LOGIN_FIREWALL, self::DASHBOARD_PATH);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(new PostAuthenticationGuardToken(new User(), 'open_id', []));

        $kernelRequestListener = new KernelRequestSubscriber($parameters, $tokenStorage, $router, $logger);
        $kernelRequestListener->onKernelRequest($requestEvent);
    }

    private function getUrlGenerator(): UrlGeneratorInterface
    {
        $router = self::createMock(Router::class);

        $router->expects(self::atLeastOnce())
            ->method('generate')
            ->willReturnOnConsecutiveCalls(
                self::OPEN_ID_REQUIRED_PATH,
                self::LOGIN_PATH,
                self::DASHBOARD_PATH
            );

        return $router;
    }

    private function getRequestEvent(string $firewall, ?string $targetUrl = null): RequestEvent
    {
        $request      = new Request([], [], ['_firewall_context' => $firewall]);
        $requestEvent = self::createMock(RequestEvent::class);

        $requestEvent
            ->method('getRequest')
            ->willReturn($request);

        if ($targetUrl) {
            $requestEvent->expects(self::once())
                ->method('setResponse')
                ->with(self::callback(static function ($response) use ($targetUrl) {
                    return $response->getTargetUrl() === $targetUrl;
                }));
        } else {
            $requestEvent->expects(self::never())
                ->method('setResponse');
        }

        return $requestEvent;
    }
}
