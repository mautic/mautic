<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
final class FunctionalWarmupTest extends MauticMysqlTestCase
{
    public function testWarmup(): void
    {
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/404');
        $this->assertResponseStatusCodeSame(404);
    }
}
