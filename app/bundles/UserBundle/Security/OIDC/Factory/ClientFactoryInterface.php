<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Factory;

use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;

interface ClientFactoryInterface
{
    public function create(ClientCredentials $clientCredentials): ClientInterface;
}
