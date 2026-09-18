<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Double\Factory;

use Mautic\UserBundle\Security\OIDC\DTO\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactoryInterface;
use Mautic\UserBundle\Security\OIDC\Service\ClientInterface;
use Mautic\UserBundle\Security\OIDC\Tests\Double\Service\Client;

final class ClientFactory implements ClientFactoryInterface
{
    public function create(ClientCredentials $clientCredentials): ClientInterface
    {
        return new Client();
    }
}
