<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Guzzle;

use GuzzleHttp\Handler\MockHandler;

trait ClientMockTrait
{
    private function getClientMockHandler(): MockHandler
    {
        return static::getContainer()->get(MockHandler::class);
    }
}
