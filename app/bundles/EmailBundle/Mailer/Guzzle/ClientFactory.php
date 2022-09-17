<?php

namespace Mautic\EmailBundle\Mailer\Guzzle;

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
