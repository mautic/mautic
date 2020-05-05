<?php

namespace Mautic\EmailBundle\Swiftmailer\Guzzle;

use GuzzleHttp\ClientInterface;
use Http\Adapter\Guzzle6\Client;

/**
 * Interface ClientFactoryInterface.
 */
interface ClientFactoryInterface
{
    /**
     * @param ClientInterface|null $client
     *
     * @return Client
     */
    public function create(ClientInterface $client = null);
}
