<?php

namespace Mautic\EmailBundle\Swiftmailer\Guzzle;

use GuzzleHttp\ClientInterface;
use Http\Adapter\Guzzle7\Client;

/**
 * Interface ClientFactoryInterface.
 */
interface ClientFactoryInterface
{
    /**
     * @return Client
     */
    public function create(ClientInterface $client = null);
}
