<?php

namespace Mautic\EmailBundle\Mailer\Guzzle;

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
