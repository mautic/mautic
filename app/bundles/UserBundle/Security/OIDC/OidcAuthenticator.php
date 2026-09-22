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
use Symfony\Contracts\Translation\TranslatorInterface;

final class OidcAuthenticator extends AbstractAuthenticator
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
        // Check if this is the OIDC callback route with code and state parameters
        return $this->parameters->isEnabled()
            && null !== $request->get('code')
            && null !== $request->get('state');
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
}
