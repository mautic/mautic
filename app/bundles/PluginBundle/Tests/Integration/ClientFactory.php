<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Integration;

use GuzzleHttp\Client;

class ClientFactory
{
    private Client $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function __invoke(): Client
    {
        return $this->httpClient;
    }
}
