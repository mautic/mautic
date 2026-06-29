<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Integration;

use GuzzleHttp\Client;

class ClientFactory
{
    public function __construct(private Client $httpClient)
    {
    }

    public function __invoke(): Client
    {
        return $this->httpClient;
    }
}
