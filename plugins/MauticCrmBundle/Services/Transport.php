<?php

namespace MauticPlugin\MauticCrmBundle\Services;

use GuzzleHttp\Client;

class Transport implements TransportInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * TransportService constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function post($uri, array $options = [])
    {
        return $this->client->request('POST', $uri, $options);
    }

    public function put($uri, array $options = [])
    {
        return $this->client->request('PUT', $uri, $options);
    }

    public function get($uri, array $options = [])
    {
        return $this->client->request('GET', $uri, $options);
    }

    public function delete($uri, array $options = [])
    {
        return $this->client->request('DELETE', $uri, $options);
    }
}
