<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Double\Factory;

use Mautic\OpenIdBundle\DTO\ClientCredentials;
use Mautic\OpenIdBundle\Factory\ClientFactoryInterface;
use Mautic\OpenIdBundle\Service\ClientInterface;
use Mautic\OpenIdBundle\Tests\Double\Service\Client;

final class ClientFactory implements ClientFactoryInterface
{
    public function create(ClientCredentials $clientCredentials): ClientInterface
    {
        return new Client();
    }
}
