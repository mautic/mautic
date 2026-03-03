<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\Authenticator;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractSsoServiceIntegration;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Authentication\AuthenticationHandler;
use Mautic\UserBundle\Security\Authentication\Token\Permissions\TokenPermissions;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\Authenticator\Passport\Badge\PluginBadge;
use Mautic\UserBundle\Security\Authenticator\PluginAuthenticator;
use Mautic\UserBundle\UserEvents;
use OAuth2\OAuth2;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class PluginAuthenticatorTest extends TestCase
{
    public function testAuthenticateByPreAuthenticationReplacesToken(): void
    {
        $firewallName             = 'main';
        $integration              = 'the integration';
        $authenticatedIntegration = 'Auth integration';
        $userIdentifier           = 'some identifier';
        $request                  = new Request(['integration' => $integration]);

        $pluginToken = new PluginToken($firewallName, $integration);

        $userProvider = $this->createMock(UserProviderInterface::class);

        $integrationService = $this->createMock(AbstractSsoServiceIntegration::class);
        $integrationHelper  = $this->createMock(IntegrationHelper::class);
        $integrationHelper->expects($this->once())
            ->method('getIntegrationObjects')
            ->with($integration, ['sso_service'], false, null, true)
            ->willReturn([$integrationService]);

        $authEvent = new AuthenticationEvent(
            null,
            $pluginToken,
            $userProvider,
            $request,
            false, // because there is no request attributes
            $integration,
            [$integrationService]
        );

        // If there will be an issue with this, then please replace with proper class name.
        // I'm not 100% sure the SSO will return a User instance.
        $authenticatedUser = $this->createMock(User::class);
        $authenticatedUser->method('getUserIdentifier')->willReturn($userIdentifier);
        $returnedPluginToken = new PluginToken($firewallName, $authenticatedIntegration);
        $returnedPluginToken->setUser($authenticatedUser);
        $returnedAuthEvent = clone $authEvent;
        // Change token. Note this also changes authenticated integration and sets user.
        $returnedAuthEvent->setToken($authenticatedIntegration, $returnedPluginToken);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(UserEvents::USER_PRE_AUTHENTICATION)
            ->willReturn(true);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($authEvent)
            ->willReturn($returnedAuthEvent);

        $authenticateResult = new PluginAuthenticator(
            $this->createMock(TokenPermissions::class),
            $dispatcher,
            $integrationHelper,
            $userProvider,
            $this->createMock(AuthenticationHandler::class),
            $this->createMock(OAuth2::class),
            $this->createMock(LoggerInterface::class),
            $firewallName
        );

        $authenticateResult = $authenticateResult->authenticate($request);
        \assert($authenticateResult instanceof SelfValidatingPassport);
        self::assertCount(2, $authenticateResult->getBadges());

        $userBadge = $authenticateResult->getBadge(UserBadge::class);
        \assert($userBadge instanceof UserBadge);
        self::assertSame($userIdentifier, $userBadge->getUserIdentifier());
        self::assertSame($authenticatedUser, $userBadge->getUser());

        $pluginBadge = $authenticateResult->getBadge(PluginBadge::class);
        \assert($pluginBadge instanceof PluginBadge);
        self::assertSame($returnedPluginToken, $pluginBadge->getPreAuthenticatedToken());
        self::assertSame($authenticatedIntegration, $pluginBadge->getAuthenticatingService());
    }

    public function testAuthenticateByPreAuthenticationSameToken(): void
    {
        $firewallName             = 'main';
        $integration              = 'the integration';
        $authenticatedIntegration = 'Auth integration';
        $userIdentifier           = 'some identifier';
        $request                  = new Request(['integration' => $integration]);

        $pluginToken = new PluginToken($firewallName, $integration);

        $userProvider = $this->createMock(UserProviderInterface::class);

        $integrationService = $this->createMock(AbstractSsoServiceIntegration::class);
        $integrationHelper  = $this->createMock(IntegrationHelper::class);
        $integrationHelper->expects($this->once())
            ->method('getIntegrationObjects')
            ->with($integration, ['sso_service'], false, null, true)
            ->willReturn([$integrationService]);

        $authEvent = new AuthenticationEvent(
            null,
            $pluginToken,
            $userProvider,
            $request,
            false, // because there is no request attributes
            $integration,
            [$integrationService]
        );

        // If there will be an issue with this, then please replace with proper class name.
        // I'm not 100% sure the SSO will return a User instance.
        $authenticatedUser = $this->createMock(User::class);
        $authenticatedUser->method('getUserIdentifier')->willReturn($userIdentifier);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(UserEvents::USER_PRE_AUTHENTICATION)
            ->willReturn(true);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($authEvent)
            ->willReturnCallback(static function (AuthenticationEvent $event) use ($authenticatedIntegration, $authenticatedUser): AuthenticationEvent {
                $event->setIsAuthenticated($authenticatedIntegration, $authenticatedUser, false);
                $event->getToken()->setUser($authenticatedUser);

                return $event;
            });

        $pluginAuthenticator = new PluginAuthenticator(
            $this->createMock(TokenPermissions::class),
            $dispatcher,
            $integrationHelper,
            $userProvider,
            $this->createMock(AuthenticationHandler::class),
            $this->createMock(OAuth2::class),
            $this->createMock(LoggerInterface::class),
            $firewallName
        );

        $authenticateResult = $pluginAuthenticator->authenticate($request);
        \assert($authenticateResult instanceof SelfValidatingPassport);
        self::assertCount(2, $authenticateResult->getBadges());

        $userBadge = $authenticateResult->getBadge(UserBadge::class);
        \assert($userBadge instanceof UserBadge);
        self::assertSame($userIdentifier, $userBadge->getUserIdentifier());
        self::assertSame($authenticatedUser, $userBadge->getUser());

        $pluginBadge = $authenticateResult->getBadge(PluginBadge::class);
        \assert($pluginBadge instanceof PluginBadge);
        self::assertEquals(new PluginToken($firewallName, $integration, $authenticatedUser), $pluginBadge->getPreAuthenticatedToken());
        self::assertSame($authenticatedIntegration, $pluginBadge->getAuthenticatingService());
    }

    public function testCreateTokenHasToken(): void
    {
        $firewallName          = 'test';
        $authenticatingService = 'Auth service';
        $encodedPassword       = 'En pass.';
        $roles                 = ['role', 'the', 'roly'];
        $pluginResponse        = new Response();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('hasListeners');
        $dispatcher->expects(self::never())->method('dispatch');

        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->expects(self::never())->method('getIntegrationObjects');

        $userProvider = $this->createMock(UserProviderInterface::class);

        $passportUser = $this->createMock(User::class);
        $passportUser->method('getPassword')->willReturn($encodedPassword);
        $passportUser->method('getRoles')->willReturn($roles);

        $userBadge = new UserBadge('', function () use ($passportUser): UserInterface {
            return $passportUser;
        });

        $pluginBadge = new PluginBadge(null, $pluginResponse, $authenticatingService);

        $passport = new SelfValidatingPassport(
            $userBadge,
            [$pluginBadge],
        );

        $pluginToken = new PluginToken(
            $firewallName,
            $authenticatingService,
            $passportUser,
            $encodedPassword,
            $roles,
            $pluginBadge->getPluginResponse()
        );

        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::once())
            ->method('setActivePermissionsOnAuthToken')
            ->with()
            ->willReturn($passportUser);

        $pluginAuthenticator = new PluginAuthenticator(
            $tokenPermissions,
            $dispatcher,
            $integrationHelper,
            $userProvider,
            $this->createMock(AuthenticationHandler::class),
            $this->createMock(OAuth2::class),
            $this->createMock(LoggerInterface::class),
            $firewallName
        );

        self::assertEquals($pluginToken, $pluginAuthenticator->createToken($passport, $firewallName));
    }

    public function testHappyPathAuthenticationSuccess(): void
    {
        $firewallName = 'test';
        $request      = new Request();
        $response     = new Response();
        $token        = new PluginToken(null);

        $authenticationHandler = $this->createMock(AuthenticationHandler::class);
        $authenticationHandler->expects(self::once())
            ->method('onAuthenticationSuccess')
            ->with($request, $token)
            ->willReturn($response);

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('remove')
            ->with(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        $request->setSession($session);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new InteractiveLoginEvent($request, $token),
                SecurityEvents::INTERACTIVE_LOGIN
            )
            ->willReturnArgument(0);

        $pluginAuthenticator = new PluginAuthenticator(
            $this->createMock(TokenPermissions::class),
            $dispatcher,
            $this->createMock(IntegrationHelper::class),
            $this->createMock(UserProviderInterface::class),
            $authenticationHandler,
            $this->createMock(OAuth2::class),
            $this->createMock(LoggerInterface::class),
            $firewallName
        );

        self::assertSame($response, $pluginAuthenticator->onAuthenticationSuccess($request, $token, $firewallName));
    }

    public function testHappyPathAuthenticationFailure(): void
    {
        $firewallName = 'test';
        $request      = new Request();
        $response     = new Response();
        $exception    = $this->createMock(AuthenticationException::class);

        $authenticationHandler = $this->createMock(AuthenticationHandler::class);
        $authenticationHandler->expects(self::once())
            ->method('onAuthenticationFailure')
            ->with($request, $exception)
            ->willReturn($response);

        $pluginAuthenticator = new PluginAuthenticator(
            $this->createMock(TokenPermissions::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(IntegrationHelper::class),
            $this->createMock(UserProviderInterface::class),
            $authenticationHandler,
            $this->createMock(OAuth2::class),
            $this->createMock(LoggerInterface::class),
            $firewallName
        );

        self::assertSame($response, $pluginAuthenticator->onAuthenticationFailure($request, $exception));
    }
}
