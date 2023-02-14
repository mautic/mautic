<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Factory;

use Mautic\OpenIdBundle\DTO\ClientCredentials;
use Mautic\OpenIdBundle\Service\ClientInterface;

interface ClientFactoryInterface
{
    public function create(ClientCredentials $clientCredentials): ClientInterface;
}
