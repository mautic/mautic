<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ClientCredentials
{
    private string $clientUrl;
    private string $clientId;
    private string $clientSecret;
    private string $mappingField;

    public function __construct(
        #[Autowire(param: 'mautic.open_id_client_url')]
        ?string $clientUrl = null,
        #[Autowire(param: 'mautic.open_id_client_id')]
        ?string $clientId = null,
        #[Autowire(param: 'mautic.open_id_client_secret')]
        ?string $clientSecret  = null,
        #[Autowire(param: 'mautic.open_id_mapping_field')]
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
