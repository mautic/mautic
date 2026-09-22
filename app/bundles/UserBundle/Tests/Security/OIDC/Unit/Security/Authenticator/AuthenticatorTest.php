<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Security\Authenticator;

use Jumbojett\OpenIDConnectClientException;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Security\OIDC\CredentialsUserProviderInterface;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\UserCredentialsFactoryInterface;
use Mautic\UserBundle\Security\OIDC\OidcAuthenticator;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AuthenticatorTest extends TestCase
{
    public function testSupportsIsTrueWhenEnabledAndWithCodeStateParams(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('get')->willReturnOnConsecutiveCalls('code', 'state');

        $authenticator = $this->buildAuthenticator();
        $response      = $authenticator->supports($request);

        $this->assertTrue($response);
    }

    public function testSupportsIsFalseWhenDisabled(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('get')->willReturnOnConsecutiveCalls('code', 'state');

        $authenticator = $this->buildAuthenticator(false);
        $response      = $authenticator->supports($request);

        $this->assertFalse($response);
    }

    public function testSupportsIsFalseWhenNoCodeStateParams(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('get')->willReturn(null);

        $authenticator = $this->buildAuthenticator();
        $response      = $authenticator->supports($request);

        $this->assertFalse($response);
    }

    public function testAuthenticateSuccess(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = $this->createMock(UserCredentialsFactoryInterface::class);
        $userProvider       = $this->createMock(CredentialsUserProviderInterface::class);
        $urlGenerator       = $this->createStub(UrlGeneratorInterface::class);
        $request            = $this->createStub(Request::class);
        $flashBag           = $this->createStub(FlashBag::class);
        $translator         = $this->createStub(TranslatorInterface::class);
        $credentials        = new UserCredentials('123', 'email@example.com', 'username', 'given_name', 'family_name');
        $user               = new User();
        $user->setEmail('email@example.com');

        $credentialsFactory->expects($this->once())
            ->method('create')
            ->willReturn($credentials);

        $userProvider->expects($this->once())
            ->method('loadUserByCredentials')
            ->with($credentials)
            ->willReturn($user);

        $authenticator = new OidcAuthenticator($parameters, $credentialsFactory, $userProvider, $urlGenerator, $flashBag, $translator);
        $passport      = $authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateThrowsAuthenticationExceptionOnOpenIDConnectClientException(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = $this->createMock(UserCredentialsFactoryInterface::class);
        $userProvider       = $this->createStub(CredentialsUserProviderInterface::class);
        $urlGenerator       = $this->createStub(UrlGeneratorInterface::class);
        $request            = $this->createStub(Request::class);
        $flashBag           = $this->createStub(FlashBag::class);
        $translator         = $this->createStub(TranslatorInterface::class);

        $credentialsFactory->expects($this->once())
            ->method('create')
            ->willThrowException(new OpenIDConnectClientException('OIDC error'));

        $authenticator = new OidcAuthenticator($parameters, $credentialsFactory, $userProvider, $urlGenerator, $flashBag, $translator);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC error');
        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsAuthenticationExceptionOnOidcException(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = $this->createMock(UserCredentialsFactoryInterface::class);
        $userProvider       = $this->createStub(CredentialsUserProviderInterface::class);
        $urlGenerator       = $this->createStub(UrlGeneratorInterface::class);
        $request            = $this->createStub(Request::class);
        $flashBag           = $this->createStub(FlashBag::class);
        $translator         = $this->createMock(TranslatorInterface::class);

        $credentialsFactory->expects($this->once())
            ->method('create')
            ->willThrowException(new OidcException('mautic.user.auth.error.invalid_subject_id'));

        $translator->expects($this->once())
            ->method('trans')
            ->with('mautic.user.auth.error.invalid_subject_id')
            ->willReturn('Translated error message');

        $authenticator = new OidcAuthenticator($parameters, $credentialsFactory, $userProvider, $urlGenerator, $flashBag, $translator);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Translated error message');
        $authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = $this->createStub(UserCredentialsFactoryInterface::class);
        $userProvider       = $this->createStub(CredentialsUserProviderInterface::class);
        $urlGenerator       = $this->createMock(UrlGeneratorInterface::class);
        $request            = $this->createStub(Request::class);
        $token              = $this->createStub(TokenInterface::class);
        $flashBag           = $this->createStub(FlashBag::class);
        $translator         = $this->createStub(TranslatorInterface::class);
        $firewallName       = 'main';

        $urlGenerator->expects($this->once())->method('generate')->willReturn('http://mautic.local');

        $authenticator = new OidcAuthenticator($parameters, $credentialsFactory, $userProvider, $urlGenerator, $flashBag, $translator);
        $result        = $authenticator->onAuthenticationSuccess($request, $token, $firewallName);
        $this->assertInstanceOf(RedirectResponse::class, $result);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame('http://mautic.local', $result->getTargetUrl());
    }

    public function testOnAuthenticationFailure(): void
    {
        $parameters         = (new ParametersBuilder())->build();
        $credentialsFactory = $this->createStub(UserCredentialsFactoryInterface::class);
        $userProvider       = $this->createStub(CredentialsUserProviderInterface::class);
        $urlGenerator       = $this->createMock(UrlGeneratorInterface::class);
        $request            = $this->createStub(Request::class);
        $exception          = $this->createStub(AuthenticationException::class);
        $flashBag           = $this->createStub(FlashBag::class);
        $translator         = $this->createStub(TranslatorInterface::class);

        $urlGenerator->expects($this->once())->method('generate')->willReturn('http://mautic.local');

        $authenticator = new OidcAuthenticator($parameters, $credentialsFactory, $userProvider, $urlGenerator, $flashBag, $translator);
        $result        = $authenticator->onAuthenticationFailure($request, $exception);
        $this->assertInstanceOf(RedirectResponse::class, $result);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame('http://mautic.local', $result->getTargetUrl());
    }

    private function buildAuthenticator(bool $isEnabled = true): OidcAuthenticator
    {
        $parameters = (new ParametersBuilder())->withIsEnabled($isEnabled)->build();

        return new OidcAuthenticator(
            $parameters,
            $this->createStub(UserCredentialsFactoryInterface::class),
            $this->createStub(CredentialsUserProviderInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(FlashBag::class),
            $this->createStub(TranslatorInterface::class)
        );
    }
}
