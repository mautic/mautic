<?php

namespace MauticPlugin\MauticCrmBundle\Services;

use GuzzleHttp\Client;

class Transport implements TransportInterface
{
    public function __construct(
        private Client $client
    ) {
    }

    public function post($uri, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->request('POST', $uri, $options);
    }

    public function put($uri, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->request('PUT', $uri, $options);
    }

    public function get($uri, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->request('GET', $uri, $options);
    }

    public function delete($uri, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->request('DELETE', $uri, $options);
    }
}
