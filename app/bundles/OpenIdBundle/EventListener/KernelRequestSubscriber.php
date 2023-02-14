<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\EventListener;

use Mautic\OpenIdBundle\DTO\Settings;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

final class KernelRequestSubscriber implements EventSubscriberInterface
{
    private Settings $parameters;
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private string $requiredUrl;
    private string $loginUrl;
    private string $dashboardUrl;

    public function __construct(
        Settings $parameters,
        TokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger
    ) {
        $this->parameters   = $parameters;
        $this->tokenStorage = $tokenStorage;
        $this->logger       = $logger;
        $this->requiredUrl  = $urlGenerator->generate('open_id_login_required', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->loginUrl     = $urlGenerator->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->dashboardUrl = $urlGenerator->generate('mautic_dashboard_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -1],
        ];
    }

    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        $firewall = $requestEvent->getRequest()->attributes->get('_firewall_context');
        $token    = $this->tokenStorage->getToken();

        if ($this->isSupportUserToken($token)) {
            return;
        }

        $redirect = $this->redirectFromOpenIDWhenDisabled($firewall)
            ?? $this->redirectFromOpenIDWhenAuthenticated($firewall, $token)
            ?? $this->redirectFromMainWhenRequired($firewall, $token)
            ?? $this->redirectFromLoginWhenAuthenticated($firewall, $token);

        if (null !== $redirect) {
            $requestEvent->setResponse($redirect);
        }
    }

    private function redirectFromOpenIDWhenDisabled(?string $firewall): ?RedirectResponse
    {
        if ($this->parameters->isEnabled() || 'security.firewall.map.context.open_id' !== $firewall) {
            return null;
        }

        $this->logger->debug('OpenID is disabled, redirecting to login page');

        return new RedirectResponse($this->loginUrl);
    }

    private function redirectFromOpenIDWhenAuthenticated(?string $firewall, ?TokenInterface $token): ?RedirectResponse
    {
        if (!$this->parameters->isEnabled() || 'security.firewall.map.context.open_id' !== $firewall || $this->isAnonymousToken($token) || (!$this->isOpenIdToken($token) && $this->parameters->isRequired())) {
            return null;
        }

        $this->logger->debug('Redirecting from OpenID to dashboard, because user is authenticated.');

        return new RedirectResponse($this->dashboardUrl);
    }

    private function redirectFromMainWhenRequired(?string $firewall, ?TokenInterface $token): ?RedirectResponse
    {
        if (!$this->parameters->isEnabled() || 'security.firewall.map.context.main' !== $firewall || $this->isOpenIdToken($token) || !$this->parameters->isRequired()) {
            return null;
        }

        $this->logger->debug('Redirecting from main to OpenID login required, because user is not authenticated with OpenID Connect.');

        return new RedirectResponse($this->requiredUrl);
    }

    private function redirectFromLoginWhenAuthenticated(?string $firewall, ?TokenInterface $token): ?RedirectResponse
    {
        if (!$this->parameters->isEnabled() || 'security.firewall.map.context.login' !== $firewall || !$this->isOpenIdToken($token)) {
            return null;
        }

        $this->logger->debug('Redirecting from login to dashboard, because user is authenticated with OpenID Connect.');

        return new RedirectResponse($this->dashboardUrl);
    }

    private function isAnonymousToken(?TokenInterface $token): bool
    {
        return null === $token || $token instanceof AnonymousToken;
    }

    private function isOpenIdToken(?TokenInterface $token): bool
    {
        return $token instanceof PostAuthenticationGuardToken && 'open_id' === $token->getProviderKey();
    }

    private function isSupportUserToken(?TokenInterface $token): bool
    {
        return $token instanceof PluginToken && $token->isSupportUser();
    }
}
