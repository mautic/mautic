<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Integration;

use GuzzleHttp\Client;

final class ClientFactory
{
    public function __construct(
        private readonly Client $httpClient,
    ) {
    }

    public function __invoke(): Client
    {
        return $this->httpClient;
    }
}
