<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Client;

use GuzzleHttp\Exception\ClientException;
use Mautic\UserBundle\Security\OIDC\Exception\AuthorizationRequestFailedException;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class Client implements ClientInterface
{
    private ClientBridgeInterface $client;
    private string $mappingField;
    private bool $hasBeenAuthenticated = false;

    public function __construct(ClientBridgeInterface $client, string $mappingField)
    {
        $this->client       = $client;
        $this->mappingField = $mappingField;
    }

    /**
     * @inerhitDoc
     */
    public function isAuthenticated(): bool
    {
        if ($this->hasBeenAuthenticated) {
            return true;
        }

        $this->hasBeenAuthenticated = $this->client->authenticate();

        return $this->hasBeenAuthenticated;
    }

    /**
     * @inerhitDoc
     */
    public function authenticate(): ?RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return null;
        }

        return new RedirectResponse($this->client->getAuthorizationUrl());
    }

    /**
     * @inerhitDoc
     */
    public function testConnection(): ?string
    {
        try {
            if ($this->isAuthenticated()) {
                return null;
            }

            $url    = $this->client->getAuthorizationUrl();
            $client = new \GuzzleHttp\Client(['cookies' => true, 'verify' => false]);
            $client->request('GET', $url);
        } catch (ClientException|AuthorizationRequestFailedException $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * @inerhitDoc
     */
    public function getVerifiedClaims(array $claims): array
    {
        $this->isAuthenticated();
        $tokenClaims = [];
        foreach ($claims as $claim) {
            $tokenClaims[$claim] = $this->client->getVerifiedClaims($claim);
        }

        return $tokenClaims;
    }

    /**
     * @inerhitDoc
     */
    public function requestUserInfo(array $claims): array
    {
        $this->isAuthenticated();
        $userInfo = [];
        foreach ($claims as $claim) {
            $userInfo[$claim] = $this->client->requestUserInfo($claim);
        }

        return $userInfo;
    }

    /**
     * @inerhitDoc
     */
    public function getMappingField(): string
    {
        return $this->mappingField;
    }
}
