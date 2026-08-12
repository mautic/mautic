<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

#[Group('database')]
final class SearchTest extends MauticMysqlTestCase
{
    public function testSearchingUsersByName(): void
    {
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, 's/users?search=name:admin');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertStringContainsString('admin', (string) $this->client->getResponse()->getContent());
    }
}
