<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Guzzle;

use GuzzleHttp\Handler\MockHandler;

trait ClientMockTrait
{
    private function getClientMockHandler(): MockHandler
    {
        $clientMockHandler = self::getContainer()->get(MockHandler::class);
        \assert($clientMockHandler instanceof MockHandler);

        return $clientMockHandler;
    }
}
