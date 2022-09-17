<?php

namespace Mautic\CoreBundle\Guzzle;

use GuzzleHttp\ClientInterface;
use Http\Adapter\Guzzle7\Client;

/**
 * Class ClientFactory.
 */
final class ClientFactory implements ClientFactoryInterface
{
    /**
     * @return Client
     */
    public function create(ClientInterface $client = null)
    {
        return new Client($client);
    }
}
