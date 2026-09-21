<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Double\Factory;

use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactoryInterface;
use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Mautic\UserBundle\Tests\Security\OIDC\Double\Service\Client;

final class ClientFactory implements ClientFactoryInterface
{
    public function create(ClientCredentials $clientCredentials): ClientInterface
    {
        return new Client();
    }
}
