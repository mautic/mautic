<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

class ClientFactory
{
    public static function stub(MockHandler $handler): ClientInterface
    {
        return new Client(['handler' => HandlerStack::create($handler)]);
    }
}
