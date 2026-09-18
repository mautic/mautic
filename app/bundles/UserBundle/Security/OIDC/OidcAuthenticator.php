<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Jumbojett\OpenIDConnectClientException;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Security\OIDC\DTO\Settings;
use Mautic\UserBundle\Security\OIDC\Exception\TranslatableException;
use Mautic\UserBundle\Security\OIDC\Factory\UserCredentialsFactoryInterface;
use Mautic\UserBundle\Security\OIDC\User\LinkerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OidcAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly Settings $parameters,
        private readonly UserCredentialsFactoryInterface $credentialsFactory,
        private readonly LinkerInterface $linker,
        private readonly OidcSubjectIdRepository $subjectIdRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FlashBag $flashBag,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Check if this is the OIDC callback route with code and state parameters
        return $this->parameters->isEnabled()
            && null !== $request->get('code')
            && null !== $request->get('state');
    }

    public function authenticate(Request $request): Passport
    {
        try {
            // Get credentials from OIDC provider
            $credentials = $this->credentialsFactory->create();

            // Use the linker to find or create the user
            $user = $this->linker->linkUser($credentials);

            // Return a self-validating passport since OIDC handles authentication
            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier(), fn () => $user)
            );
        } catch (OpenIDConnectClientException $e) {
            throw new AuthenticationException($e->getMessage(), 0, $e);
        } catch (TranslatableException $e) {
            throw new AuthenticationException($this->translator->trans($e->getMessage()), 0, $e);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to dashboard or intended URL
        return new RedirectResponse(
            $this->urlGenerator->generate('mautic_dashboard_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->flashBag->add($exception->getMessage(), [], FlashBag::LEVEL_ERROR);

        return new RedirectResponse(
            $this->urlGenerator->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }
}
