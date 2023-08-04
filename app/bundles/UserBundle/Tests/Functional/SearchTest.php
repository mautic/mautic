<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SearchTest extends MauticMysqlTestCase
{
    public function testSearchingUsersByName(): void
    {
        $this->client->request('GET', 's/users?search=name:admin');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertStringContainsString('admin', $this->client->getResponse()->getContent());
    }
}
