<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Jumbojett\OpenIDConnectClientException;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Security\OIDC\Factory\UserCredentialsFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OidcAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly Settings $parameters,
        private readonly UserCredentialsFactoryInterface $credentialsFactory,
        private readonly CredentialsUserProviderInterface $userProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FlashBag $flashBag,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(Request $request): bool
    {
        // Support OIDC callback when code and state parameters are present
        // This can come via either the login or login_check route depending on provider configuration
        $route = $request->attributes->get('_route');

        return $this->parameters->isEnabled()
            && ('mautic_oidc_check' === $route || 'mautic_oidc_login' === $route)
            && null !== $request->query->get('code')
            && null !== $request->query->get('state');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        try {
            // Get credentials from OIDC provider
            $credentials = $this->credentialsFactory->create();

            // Load or create the user through the user provider
            $user = $this->userProvider->loadUserByCredentials($credentials);

            // Return a self-validating passport since OIDC handles authentication
            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier(), fn (): UserInterface => $user)
            );
        } catch (OpenIDConnectClientException $e) {
            throw new AuthenticationException($e->getMessage(), 0, $e);
        } catch (OidcException $e) {
            throw new AuthenticationException($this->translator->trans($e->getMessage()), 0, $e);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): RedirectResponse
    {
        // Redirect to dashboard or intended URL
        return new RedirectResponse(
            $this->urlGenerator->generate('mautic_dashboard_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $this->flashBag->add($exception->getMessage(), [], FlashBag::LEVEL_ERROR);

        return new RedirectResponse(
            $this->urlGenerator->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * Called when authentication is needed but not provided.
     * This is the entry point that starts the authentication process.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        // Redirect to the OIDC login page to start authentication
        return new RedirectResponse(
            $this->urlGenerator->generate('mautic_oidc_login', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }
}
