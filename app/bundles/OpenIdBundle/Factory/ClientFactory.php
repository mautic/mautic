<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Factory;

use Mautic\OpenIdBundle\DTO\ClientCredentials;
use Mautic\OpenIdBundle\Event\RegisterScopesEvent;
use Mautic\OpenIdBundle\Service\Client;
use Mautic\OpenIdBundle\Service\ClientInterface;
use Mautic\OpenIdBundle\Service\OpenIDConnectBridge;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ClientFactory implements ClientFactoryInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private EventDispatcherInterface $eventDispatcher;
    private SessionInterface $session;

    public function __construct(UrlGeneratorInterface $urlGenerator, EventDispatcherInterface $eventDispatcher, SessionInterface $session)
    {
        $this->urlGenerator      = $urlGenerator;
        $this->eventDispatcher   = $eventDispatcher;
        $this->session           = $session;
    }

    public function create(ClientCredentials $clientCredentials): ClientInterface
    {
        $redirectUrl = $this->urlGenerator->generate('open_id_login_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $scopesEvent = new RegisterScopesEvent();
        $this->eventDispatcher->dispatch($scopesEvent);
        $scopes = $scopesEvent->getScopes();
        $client = new OpenIDConnectBridge($clientCredentials->getClientUrl(), $clientCredentials->getClientId(), $clientCredentials->getClientSecret());

        $client->setSession($this->session);
        $client->setAllowImplicitFlow(true);
        $client->addScope($scopes);
        $client->setRedirectURL($redirectUrl);

        return new Client($client, $clientCredentials->getMappingField());
    }
}
