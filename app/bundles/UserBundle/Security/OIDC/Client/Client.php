<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Client;

use GuzzleHttp\Exception\ClientException;
use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Exclude]
final class Client implements ClientInterface
{
    private bool $hasBeenAuthenticated = false;

    public function __construct(private readonly ClientBridgeInterface $client, private readonly string $mappingField)
    {
    }

    public function isAuthenticated(): bool
    {
        if ($this->hasBeenAuthenticated) {
            return true;
        }

        $this->hasBeenAuthenticated = $this->client->authenticate();

        return $this->hasBeenAuthenticated;
    }

    public function authenticate(): ?RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return null;
        }

        return new RedirectResponse($this->client->getAuthorizationUrl());
    }

    public function testConnection(): ?string
    {
        try {
            if ($this->isAuthenticated()) {
                return null;
            }

            $url    = $this->client->getAuthorizationUrl();
            $client = new \GuzzleHttp\Client(['cookies' => true, 'verify' => false]);
            $client->request('GET', $url);
        } catch (ClientException|OidcAuthorizationException $e) {
            return $e->getMessage();
        }

        return null;
    }

    public function getVerifiedClaims(array $claims): array
    {
        $this->isAuthenticated();
        $tokenClaims = [];
        foreach ($claims as $claim) {
            $tokenClaims[$claim] = $this->client->getVerifiedClaims($claim);
        }

        return $tokenClaims;
    }

    public function requestUserInfo(array $claims): array
    {
        $this->isAuthenticated();
        $userInfo = [];
        foreach ($claims as $claim) {
            $userInfo[$claim] = $this->client->requestUserInfo($claim);
        }

        return $userInfo;
    }

    public function getMappingField(): string
    {
        return $this->mappingField;
    }
}
