<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PointControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionWithoutPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexActionWithPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points/1');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAction(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points/new');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
