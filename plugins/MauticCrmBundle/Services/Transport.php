<?php

namespace MauticPlugin\MauticCrmBundle\Services;

use GuzzleHttp\Client;

class Transport implements TransportInterface
{
<<<<<<< HEAD
    public function __construct(private Client $client)
=======
    private \GuzzleHttp\Client $client;

    public function __construct(Client $client)
>>>>>>> 11b4805f88 ([type-declarations] Re-run rector rules on plugins, Report, Sms, User, Lead, Dynamic, Config bundles)
    {
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
