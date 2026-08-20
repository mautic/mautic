<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

final class ClientFactory
{
    public static function stub(MockHandler $handler): Client
    {
        return new Client(['handler' => HandlerStack::create($handler)]);
    }
}
