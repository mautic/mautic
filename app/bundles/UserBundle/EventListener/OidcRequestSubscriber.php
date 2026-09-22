<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\OIDC\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final readonly class OidcRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Settings $parameters,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
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

        return new RedirectResponse($this->urlGenerator->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    private function redirectFromOpenIDWhenAuthenticated(?string $firewall, ?TokenInterface $token): ?RedirectResponse
    {
        if (!$this->parameters->isEnabled() || 'security.firewall.map.context.open_id' !== $firewall || $this->isAnonymousToken($token) || (!$this->isOpenIdToken($token) && $this->parameters->isRequired())) {
            return null;
        }

        $this->logger->debug('Redirecting from OpenID to dashboard, because user is authenticated.');

        return new RedirectResponse($this->urlGenerator->generate('mautic_dashboard_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    private function redirectFromMainWhenRequired(?string $firewall, ?TokenInterface $token): ?RedirectResponse
    {
        if (!$this->parameters->isEnabled() || 'security.firewall.map.context.main' !== $firewall || $this->isOpenIdToken($token) || !$this->parameters->isRequired()) {
            return null;
        }

        $this->logger->debug('Redirecting from main to OpenID login required, because user is not authenticated with OpenID Connect.');

        return new RedirectResponse($this->urlGenerator->generate('mautic_oidc_required', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    private function redirectFromLoginWhenAuthenticated(?string $firewall, ?TokenInterface $token): ?RedirectResponse
    {
        if (!$this->parameters->isEnabled() || 'security.firewall.map.context.login' !== $firewall || !$this->isOpenIdToken($token)) {
            return null;
        }

        $this->logger->debug('Redirecting from login to dashboard, because user is authenticated with OpenID Connect.');

        return new RedirectResponse($this->urlGenerator->generate('mautic_dashboard_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    private function isAnonymousToken(?TokenInterface $token): bool
    {
        // In Symfony 7, null token represents anonymous (AnonymousToken was removed)
        return null === $token || null === $token->getUser();
    }

    private function isOpenIdToken(?TokenInterface $token): bool
    {
        if (null === $token) {
            return false;
        }

        // Check if token has firewall attribute (set by Symfony's authenticator)
        if (method_exists($token, 'hasAttribute') && $token->hasAttribute('_firewall_name')) {
            return 'open_id' === $token->getAttribute('_firewall_name');
        }

        // Fallback: check PluginToken's providerKey
        if ($token instanceof PluginToken) {
            return 'open_id' === $token->getProviderKey();
        }

        return false;
    }

    private function isSupportUserToken(?TokenInterface $token): bool
    {
        return $token instanceof PluginToken && $token->isSupportUser();
    }
}
