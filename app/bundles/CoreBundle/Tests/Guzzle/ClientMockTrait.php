<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Guzzle;

use GuzzleHttp\Handler\MockHandler;

trait ClientMockTrait
{
    private function getClientMockHandler(): MockHandler
    {
        return static::getContainer()->get(MockHandler::class);
    }
}
