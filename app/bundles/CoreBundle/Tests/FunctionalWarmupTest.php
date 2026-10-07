<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests;

use Symfony\Component\HttpFoundation\Request;

final class FunctionalWarmupTest extends MauticMysqlTestCase
{
    public function testWarmup(): void
    {
        $this->client->request(Request::METHOD_GET, '/404');
        $this->assertResponseStatusCodeSame(404);
    }
}
