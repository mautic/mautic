<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

class FunctionalWarmupTest extends MauticMysqlTestCase
{
    public function testWarmup(): void
    {
        $this->client->request('GET', '/404');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }
}
