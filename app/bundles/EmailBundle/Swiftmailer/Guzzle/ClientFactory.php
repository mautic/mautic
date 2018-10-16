<?php

namespace Mautic\EmailBundle\Swiftmailer\Guzzle;

use GuzzleHttp\ClientInterface;
use Http\Adapter\Guzzle6\Client;

/**
 * Class ClientFactory.
 */
final class ClientFactory implements ClientFactoryInterface
{
    /**
     * @param ClientInterface|null $client
     *
     * @return Client
     */
    public function create(ClientInterface $client = null)
    {
        return new Client($client);
    }
}
