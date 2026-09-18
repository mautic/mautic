<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\DTO;

final class ClientCredentials
{
    private string $clientUrl;
    private string $clientId;
    private string $clientSecret;
    private string $mappingField;

    public function __construct(
        ?string $clientUrl = null,
        ?string $clientId = null,
        ?string $clientSecret  = null,
        ?string $mappingField  = null,
    ) {
        $this->clientUrl     = (string) $clientUrl;
        $this->clientId      = (string) $clientId;
        $this->clientSecret  = (string) $clientSecret;
        $this->mappingField  = (string) $mappingField;
    }

    public function getClientUrl(): string
    {
        return $this->clientUrl;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getMappingField(): string
    {
        return $this->mappingField;
    }
}
