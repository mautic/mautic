<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Security\Authenticator;

use Mautic\CoreBundle\Service\FlashBag;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\UserCredentialsFactoryInterface;
use Mautic\UserBundle\Security\OIDC\Repository\SubjectIdRepository;
use Mautic\UserBundle\Security\OIDC\Security\Authenticator\Authenticator;
use Mautic\UserBundle\Security\OIDC\Security\Provider\CredentialsUserProviderInterface;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AuthenticatorTest extends TestCase
{
    public function testStart(): void
    {
        $request       = self::createMock(Request::class);
        $authenticator = $this->buildAuthenticator();
        $response      = $authenticator->start($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testSupportsIsTrueWhenEnabledAndWithCodeStateParams(): void
    {
        $request = self::createMock(Request::class);
        $request->method('get')->willReturnOnConsecutiveCalls('code', 'state');

        $authenticator = $this->buildAuthenticator();
        $response      = $authenticator->supports($request);

        self::assertTrue($response);
    }

    public function testSupportsIsFalseWhenDisabled(): void
    {
        $request = self::createMock(Request::class);
        $request->method('get')->willReturnOnConsecutiveCalls('code', 'state');

        $authenticator = $this->buildAuthenticator(false);
        $response      = $authenticator->supports($request);

        self::assertFalse($response);
    }

    public function testSupportsIsFalseWhenNoCodeStateParams(): void
    {
        $request = self::createMock(Request::class);
        $request->method('get')->willReturnOnConsecutiveCalls(null, null);

        $authenticator = $this->buildAuthenticator();
        $response      = $authenticator->supports($request);

        self::assertFalse($response);
    }

    public function testGetCredentials(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = self::createMock(UserCredentialsFactoryInterface::class);
        $subjectIdRepo      = self::createMock(SubjectIdRepository::class);
        $urlGenerator       = self::createMock(UrlGeneratorInterface::class);
        $request            = self::createMock(Request::class);
        $flashBag           = self::createMock(FlashBag::class);
        $translator         = self::createMock(TranslatorInterface::class);
        $credentials        = new UserCredentials('123', 'email', 'username', 'given_name', 'family_name');

        $credentialsFactory->expects(self::once())
            ->method('create')
            ->willReturn($credentials);

        $authenticator            = new Authenticator($parameters, $credentialsFactory, $subjectIdRepo, $urlGenerator, $flashBag, $translator);
        $authenticatedCredentials = $authenticator->getCredentials($request);

        self::assertEquals($credentials, $authenticatedCredentials);
    }

    public function testGetUser(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = self::createMock(UserCredentialsFactoryInterface::class);
        $subjectIdRepo      = self::createMock(SubjectIdRepository::class);
        $urlGenerator       = self::createMock(UrlGeneratorInterface::class);
        $credentials        = new UserCredentials('123', 'email', 'username', 'given_name', 'family_name');
        $userProvider       = self::createMock(CredentialsUserProviderInterface::class);
        $flashBag           = self::createMock(FlashBag::class);
        $translator         = self::createMock(TranslatorInterface::class);

        $user = new User();
        $userProvider->expects(self::once())->method('loadUserByCredentials')->willReturn($user);

        $authenticator     = new Authenticator($parameters, $credentialsFactory, $subjectIdRepo, $urlGenerator, $flashBag, $translator);
        $authenticatedUser = $authenticator->getUser($credentials, $userProvider);

        self::assertSame($user, $authenticatedUser);
    }

    public function testCheckCredentialsIsTrueWhenSubjectIdIsFound(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = self::createMock(UserCredentialsFactoryInterface::class);
        $subjectIdRepo      = self::createMock(SubjectIdRepository::class);
        $urlGenerator       = self::createMock(UrlGeneratorInterface::class);
        $flashBag           = self::createMock(FlashBag::class);
        $translator         = self::createMock(TranslatorInterface::class);
        $credentials        = new UserCredentials('123', 'email', 'username', 'given_name', 'family_name');
        $user               = new User();

        $subjectIdRepo->expects(self::once())->method('count')->willReturn(1);

        $authenticator      = new Authenticator($parameters, $credentialsFactory, $subjectIdRepo, $urlGenerator, $flashBag, $translator);
        $isValidCredentials = $authenticator->checkCredentials($credentials, $user);

        self::assertTrue($isValidCredentials);
    }

    public function testCheckCredentialsIsFalseWhenSubjectIdIsNotFound(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = self::createMock(UserCredentialsFactoryInterface::class);
        $subjectIdRepo      = self::createMock(SubjectIdRepository::class);
        $urlGenerator       = self::createMock(UrlGeneratorInterface::class);
        $flashBag           = self::createMock(FlashBag::class);
        $translator         = self::createMock(TranslatorInterface::class);
        $credentials        = new UserCredentials('123', 'email', 'username', 'given_name', 'family_name');
        $user               = new User();

        $subjectIdRepo->expects(self::once())->method('count')->willReturn(0);

        $authenticator      = new Authenticator($parameters, $credentialsFactory, $subjectIdRepo, $urlGenerator, $flashBag, $translator);
        $isValidCredentials = $authenticator->checkCredentials($credentials, $user);

        self::assertFalse($isValidCredentials);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = self::createMock(UserCredentialsFactoryInterface::class);
        $subjectIdRepo      = self::createMock(SubjectIdRepository::class);
        $urlGenerator       = self::createMock(UrlGeneratorInterface::class);
        $request            = self::createMock(Request::class);
        $token              = self::createMock(TokenInterface::class);
        $flashBag           = self::createMock(FlashBag::class);
        $translator         = self::createMock(TranslatorInterface::class);
        $providerKey        = 'providerKey';

        $urlGenerator->expects(self::once())->method('generate')->willReturn('http://mautic.local');

        $authenticator = new Authenticator($parameters, $credentialsFactory, $subjectIdRepo, $urlGenerator, $flashBag, $translator);
        $result        = $authenticator->onAuthenticationSuccess($request, $token, $providerKey);
        \assert($result instanceof RedirectResponse);

        self::assertSame(302, $result->getStatusCode());
        self::assertSame('http://mautic.local', $result->getTargetUrl());
    }

    public function testOnAuthenticationFailure(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = self::createMock(UserCredentialsFactoryInterface::class);
        $subjectIdRepo      = self::createMock(SubjectIdRepository::class);
        $urlGenerator       = self::createMock(UrlGeneratorInterface::class);
        $request            = self::createMock(Request::class);
        $exception          = self::createMock(AuthenticationException::class);
        $flashBag           = self::createMock(FlashBag::class);
        $translator         = self::createMock(TranslatorInterface::class);

        $urlGenerator->expects(self::once())->method('generate')->willReturn('http://mautic.local');

        $authenticator = new Authenticator($parameters, $credentialsFactory, $subjectIdRepo, $urlGenerator, $flashBag, $translator);
        $result        = $authenticator->onAuthenticationFailure($request, $exception);
        \assert($result instanceof RedirectResponse);

        self::assertSame(302, $result->getStatusCode());
        self::assertSame('http://mautic.local', $result->getTargetUrl());
    }

    public function testSupportsRememberMe(): void
    {
        $authenticator = $this->buildAuthenticator();

        self::assertFalse($authenticator->supportsRememberMe());
    }

    private function buildAuthenticator(bool $isEnabled = true): Authenticator
    {
        $parameters         = (new ParametersBuilder())->withIsEnabled($isEnabled)->build();
        $credentialsFactory = self::createMock(UserCredentialsFactoryInterface::class);
        $subjectIdRepo      = self::createMock(SubjectIdRepository::class);
        $urlGenerator       = self::createMock(UrlGeneratorInterface::class);
        $flashBag           = self::createMock(FlashBag::class);
        $translator         = self::createMock(TranslatorInterface::class);

        return new Authenticator($parameters, $credentialsFactory, $subjectIdRepo, $urlGenerator, $flashBag, $translator);
    }
}
