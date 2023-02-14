<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Security\Authenticator;

use Jumbojett\OpenIDConnectClientException;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\OpenIdBundle\DTO\Settings;
use Mautic\OpenIdBundle\DTO\UserCredentials;
use Mautic\OpenIdBundle\Exception\TranslatableException;
use Mautic\OpenIdBundle\Factory\UserCredentialsFactoryInterface;
use Mautic\OpenIdBundle\Repository\SubjectIdRepository;
use Mautic\OpenIdBundle\Security\Provider\CredentialsUserProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Authenticator extends AbstractGuardAuthenticator
{
    private Settings $parameters;
    private UserCredentialsFactoryInterface $credentialsFactory;
    private SubjectIdRepository $subjectIdRepository;
    private UrlGeneratorInterface $urlGenerator;
    private FlashBag $flashBag;
    private TranslatorInterface $translator;

    public function __construct(
        Settings $parameters,
        UserCredentialsFactoryInterface $credentialsFactory,
        SubjectIdRepository $subjectIdRepository,
        UrlGeneratorInterface $urlGenerator,
        FlashBag $flashBag,
        TranslatorInterface $translator
    ) {
        $this->parameters          = $parameters;
        $this->credentialsFactory  = $credentialsFactory;
        $this->subjectIdRepository = $subjectIdRepository;
        $this->urlGenerator        = $urlGenerator;
        $this->flashBag            = $flashBag;
        $this->translator          = $translator;
    }

    public function start(Request $request, ?AuthenticationException $authException = null)
    {
        return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
    }

    public function supports(Request $request)
    {
        return $this->parameters->isEnabled() && $request->get('code') && $request->get('state');
    }

    public function getCredentials(Request $request)
    {
        // recast to AuthenticationException to trigger onAuthenticationFailure
        try {
            return $this->credentialsFactory->create();
        } catch (OpenIDConnectClientException $e) {
            throw new AuthenticationException($e->getMessage());
        } catch (TranslatableException $e) {
            throw new AuthenticationException($this->translator->trans($e->getMessage()));
        }
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        \assert($credentials instanceof UserCredentials);
        \assert($userProvider instanceof CredentialsUserProviderInterface);

        // recast to AuthenticationException to trigger onAuthenticationFailure
        try {
            return $userProvider->loadUserByCredentials($credentials);
        } catch (TranslatableException $e) {
            throw new AuthenticationException($this->translator->trans($e->getMessage()));
        }
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return 1 === $this->subjectIdRepository->count(['subjectID' => $credentials->getId(), 'user' => $user]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->flashBag->add($exception->getMessage(), [], FlashBag::LEVEL_ERROR);

        return new RedirectResponse($this->urlGenerator->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $url = $this->urlGenerator->generate('mautic_dashboard_index', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return new RedirectResponse($url);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
