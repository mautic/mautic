<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\Authenticator;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractSsoServiceIntegration;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\Authenticator\Passport\Badge\PasswordStrengthBadge;
use Mautic\UserBundle\Security\Authenticator\SsoAuthenticator;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Mautic\UserBundle\UserEvents;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class SsoAuthenticatorTest extends TestCase
{
    /**
     * @dataProvider provideIsPost
     */
    public function testIsPost(string $method, bool $isPost, bool $expected): void
    {
        $path              = '/path';
        $options           = ['post_only' => $isPost, 'check_path' => $path, 'form_only' => false];
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProviderInterface::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $request = new Request();
        $request->server->set('REQUEST_METHOD', $method);

        if (true === $expected) {
            $httpUtils->method('checkRequestPath')
                ->with($request, $path)
                ->willReturn(true);

            if ($isPost) {
                $request->request->set('integration', 'integration');
            } else {
                $request->query->set('integration', 'integration');
            }
        }

        self::assertSame($expected, $authenticator->supports($request));
    }

    public static function provideIsPost(): \Generator
    {
        yield 'is not POST and POST only' => [Request::METHOD_GET, true, false];
        yield 'is POST and POST only' => [Request::METHOD_POST, true, true];
        yield 'is not POST and not POST only' => [Request::METHOD_GET, false, true];
        yield 'is POST and not POST only' => [Request::METHOD_POST, false, true];
    }

    /**
     * @dataProvider provideCheckPath
     */
    public function testCheckPath(bool $expected): void
    {
        $path              = '/path';
        $options           = ['post_only' => true, 'check_path' => $path, 'form_only' => false];
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProviderInterface::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $request = new Request();
        $request->server->set('REQUEST_METHOD', Request::METHOD_POST);
        $request->request->set('integration', 'integration');

        $httpUtils->expects(self::once())
            ->method('checkRequestPath')
            ->with($request, $path)
            ->willReturn($expected);

        self::assertSame($expected, $authenticator->supports($request));
    }

    public static function provideCheckPath(): \Generator
    {
        yield 'Is correct path' => [true];
        yield 'Is not correct path' => [false];
    }

    /**
     * @dataProvider provideFormOnly
     */
    public function testFormOnly(string $mimeType, bool $isForm, bool $expected): void
    {
        $path              = '/path';
        $options           = ['post_only' => true, 'check_path' => $path, 'form_only' => $isForm];
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProviderInterface::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $request = new Request();
        $request->server->set('REQUEST_METHOD', Request::METHOD_POST);
        $request->request->set('integration', 'integration');

        $request->headers->set('CONTENT_TYPE', $mimeType);

        $httpUtils->expects(self::once())
            ->method('checkRequestPath')
            ->with($request, $path)
            ->willReturn(true);

        self::assertSame($expected, $authenticator->supports($request));
    }

    public static function provideFormOnly(): \Generator
    {
        yield 'is not form and form only' => ['application/json', true, false];
        yield 'is form and form only' => ['application/x-www-form-urlencoded', true, true];
        yield 'is not form and not form only' => ['application/json', false, true];
        yield 'is form and not form only' => ['application/x-www-form-urlencoded', false, true];
    }

    /**
     * @dataProvider provideRequestIntegrationParameter
     */
    public function testHasRequestIntegrationParameter(?bool $addToPost, bool $isPost, bool $expected): void
    {
        $path              = '/path';
        $options           = ['post_only' => $isPost, 'check_path' => $path, 'form_only' => false];
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProviderInterface::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $request = new Request();
        $request->server->set('REQUEST_METHOD', Request::METHOD_POST);

        if (null !== $addToPost) {
            if ($addToPost) {
                $request->request->set('integration', 'integration');
            } else {
                $request->query->set('integration', 'integration');
            }
        }

        $httpUtils->expects(self::once())
            ->method('checkRequestPath')
            ->with($request, $path)
            ->willReturn(true);

        self::assertSame($expected, $authenticator->supports($request));
    }

    public static function provideRequestIntegrationParameter(): \Generator
    {
        yield 'has POST parameter and is POST only' => [true, true, true];
        yield 'has no POST parameter and is POST only' => [false, true, false];
        yield 'has GET parameter and is not POST only' => [false, false, true];
        yield 'has POST parameter and is not POST only' => [true, false, true];
        yield 'has no POST or GET parameter and is not POST only' => [null, false, false];
    }

    /**
     * @dataProvider provideEnableCsrf
     */
    public function testBadges(bool $enableCsrf): void
    {
        $username          = 'mautic';
        $password          = 'pw';
        $integration       = 'integration';
        $csrfToken         = 'token';
        $options           = ['post_only' => true, 'enable_csrf' => $enableCsrf];
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProviderInterface::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $session           = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with(SecurityRequestAttributes::LAST_USERNAME, $username);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $request = new Request();
        $request->setSession($session);
        $request->request->set('_username', $username);
        $request->request->set('_password', $password);
        $request->request->set('integration', $integration);
        $request->request->set('_csrf_token', $csrfToken);

        $passport = $authenticator->authenticate($request);
        $badges   = $passport->getBadges();
        self::assertCount($enableCsrf ? 4 : 3, $badges);

        $userBadge = $passport->getBadge(UserBadge::class);
        \assert($userBadge instanceof UserBadge);
        self::assertSame($username, $userBadge->getUserIdentifier());

        $passwordBadge = $passport->getBadge(PasswordCredentials::class);
        \assert($passwordBadge instanceof PasswordCredentials);
        self::assertSame($password, $passwordBadge->getPassword());

        self::assertTrue($passport->hasBadge(RememberMeBadge::class));

        // Badge will be added later by PasswordStrengthSubscriber
        $passwordStrengthBadge = $passport->getBadge(PasswordStrengthBadge::class);
        self::assertNull($passwordStrengthBadge);

        if (!$enableCsrf) {
            self::assertFalse($passport->hasBadge(CsrfTokenBadge::class));

            return;
        }

        $csrfTokenBadge = $passport->getBadge(CsrfTokenBadge::class);
        \assert($csrfTokenBadge instanceof CsrfTokenBadge);
        self::assertSame($csrfToken, $csrfTokenBadge->getCsrfToken());
        self::assertSame('authenticate', $csrfTokenBadge->getCsrfTokenId());
    }

    public static function provideEnableCsrf(): \Generator
    {
        yield 'enable csrf' => [true];
        yield 'not enable csrf' => [false];
    }

    public function testAuthenticateDoesNotLoadFromProviderAndNoListenersReturnsNoUser(): void
    {
        $username          = 'mautic';
        $password          = 'pw';
        $integration       = 'integration';
        $csrfToken         = 'token';
        $options           = ['post_only' => true];
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProvider::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $session           = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with(SecurityRequestAttributes::LAST_USERNAME, $username);

        $integrations = [$this->createMock(AbstractSsoServiceIntegration::class)];
        $integrationHelper->expects(self::once())
            ->method('getIntegrationObjects')
            ->with($integration, ['sso_form'], false, null, true)
            ->willReturn($integrations);

        $userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($username)
            ->willThrowException(new UserNotFoundException());

        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(UserEvents::USER_FORM_AUTHENTICATION)
            ->willReturn(false);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $request = new Request();
        $request->setSession($session);
        $request->request->set('_username', $username);
        $request->request->set('_password', $password);
        $request->request->set('integration', $integration);
        $request->request->set('_csrf_token', $csrfToken);

        $passport = $authenticator->authenticate($request);

        $userBadge = $passport->getBadge(UserBadge::class);
        \assert($userBadge instanceof UserBadge);
        self::assertSame($username, $userBadge->getUserIdentifier());

        $this->expectException(UserNotFoundException::class);

        $userBadge->getUser();
    }

    public function testAuthenticateLoadsFromProviderAndNoListenersReturnsUser(): void
    {
        $username          = 'mautic';
        $password          = 'pw';
        $integration       = 'integration';
        $csrfToken         = 'token';
        $options           = ['post_only' => true];
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProvider::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $session           = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with(SecurityRequestAttributes::LAST_USERNAME, $username);

        $integrations = [$this->createMock(AbstractSsoServiceIntegration::class)];
        $integrationHelper->expects(self::once())
            ->method('getIntegrationObjects')
            ->with($integration, ['sso_form'], false, null, true)
            ->willReturn($integrations);

        $user = $this->createMock(User::class);
        $user->expects(self::once())
            ->method('getRoles')
            ->willReturn([]);
        $userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($username)
            ->willReturn($user);

        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(UserEvents::USER_FORM_AUTHENTICATION)
            ->willReturn(false);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $request = new Request();
        $request->setSession($session);
        $request->request->set('_username', $username);
        $request->request->set('_password', $password);
        $request->request->set('integration', $integration);
        $request->request->set('_csrf_token', $csrfToken);

        $passport = $authenticator->authenticate($request);

        $userBadge = $passport->getBadge(UserBadge::class);
        \assert($userBadge instanceof UserBadge);
        self::assertSame($username, $userBadge->getUserIdentifier());
        self::assertSame($user, $userBadge->getUser());
    }

    public function testAuthenticateListenerForcesFailure(): void
    {
        $username          = 'mautic';
        $password          = 'pw';
        $integration       = 'integration';
        $csrfToken         = 'token';
        $userRoles         = ['ROLE'];
        $options           = ['post_only' => true];
        $failedMessage     = 'Failure';
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProvider::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $session           = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with(SecurityRequestAttributes::LAST_USERNAME, $username);

        $integrations = [$this->createMock(AbstractSsoServiceIntegration::class)];
        $integrationHelper->expects(self::once())
            ->method('getIntegrationObjects')
            ->with($integration, ['sso_form'], false, null, true)
            ->willReturn($integrations);

        $user = $this->createMock(User::class);
        $user->expects(self::once())
            ->method('getRoles')
            ->willReturn($userRoles);
        $userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($username)
            ->willReturn($user);

        $request = new Request();
        $request->setSession($session);
        $request->request->set('_username', $username);
        $request->request->set('_password', $password);
        $request->request->set('integration', $integration);
        $request->request->set('_csrf_token', $csrfToken);

        $token = new PluginToken(
            null,
            $integration,
            $username,
            $password,
            $userRoles,
        );

        $callEvent = new AuthenticationEvent(
            $user,
            $token,
            $userProvider,
            $request,
            false,
            $integration,
            $integrations
        );

        $returnEvent = clone $callEvent;
        $returnEvent->setFailedAuthenticationMessage($failedMessage);
        $returnEvent->setIsFailedAuthentication();

        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(UserEvents::USER_FORM_AUTHENTICATION)
            ->willReturn(true);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with($callEvent, UserEvents::USER_FORM_AUTHENTICATION)
            ->willReturn($returnEvent);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $passport = $authenticator->authenticate($request);

        $userBadge = $passport->getBadge(UserBadge::class);
        \assert($userBadge instanceof UserBadge);
        self::assertSame($username, $userBadge->getUserIdentifier());

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage($failedMessage);

        $userBadge->getUser();
    }

    public function testAuthenticateListenerLoadsUser(): void
    {
        $username          = 'mautic';
        $password          = 'pw';
        $integration       = 'integration';
        $csrfToken         = 'token';
        $options           = ['post_only' => true];
        $httpUtils         = $this->createMock(HttpUtils::class);
        $userProvider      = $this->createMock(UserProvider::class);
        $successHandler    = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $failureHandler    = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $session           = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with(SecurityRequestAttributes::LAST_USERNAME, $username);

        $integrations = [$this->createMock(AbstractSsoServiceIntegration::class)];
        $integrationHelper->expects(self::once())
            ->method('getIntegrationObjects')
            ->with($integration, ['sso_form'], false, null, true)
            ->willReturn($integrations);

        $user = $this->createMock(User::class);
        $userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($username)
            ->willThrowException(new UserNotFoundException());

        $request = new Request();
        $request->setSession($session);
        $request->request->set('_username', $username);
        $request->request->set('_password', $password);
        $request->request->set('integration', $integration);
        $request->request->set('_csrf_token', $csrfToken);

        $token = new PluginToken(
            null,
            $integration,
            $username,
            '',
            [],
        );

        $callEvent = new AuthenticationEvent(
            $username,
            $token,
            $userProvider,
            $request,
            false,
            $integration,
            $integrations
        );

        $returnEvent = clone $callEvent;
        $returnEvent->setIsAuthenticated($integration, $user, false);

        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(UserEvents::USER_FORM_AUTHENTICATION)
            ->willReturn(true);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with($callEvent, UserEvents::USER_FORM_AUTHENTICATION)
            ->willReturn($returnEvent);

        $authenticator = new SsoAuthenticator(
            $options,
            $httpUtils,
            $userProvider,
            $successHandler,
            $failureHandler,
            $integrationHelper,
            $dispatcher
        );

        $passport = $authenticator->authenticate($request);

        $userBadge = $passport->getBadge(UserBadge::class);
        \assert($userBadge instanceof UserBadge);
        self::assertSame($username, $userBadge->getUserIdentifier());

        self::assertSame($user, $userBadge->getUser());
    }
}
