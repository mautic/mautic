<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Factory;

use Mautic\UserBundle\Security\OIDC\Client\Client;
use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Mautic\UserBundle\Security\OIDC\Client\OpenIDConnectBridge;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\RegisterScopesEvent;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsAlias(ClientFactoryInterface::class)]
final class ClientFactory implements ClientFactoryInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function create(ClientCredentials $clientCredentials): ClientInterface
    {
        $redirectUrl = $this->urlGenerator->generate('open_id_login_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $scopesEvent = new RegisterScopesEvent();
        $this->eventDispatcher->dispatch($scopesEvent);
        $scopes = $scopesEvent->getScopes();
        $client = new OpenIDConnectBridge($clientCredentials->getClientUrl(), $clientCredentials->getClientId(), $clientCredentials->getClientSecret());

        $session = $this->requestStack->getSession();
        $client->setSession($session);
        $client->setAllowImplicitFlow(true);
        $client->addScope($scopes);
        $client->setRedirectURL($redirectUrl);

        return new Client($client, $clientCredentials->getMappingField());
    }
}
