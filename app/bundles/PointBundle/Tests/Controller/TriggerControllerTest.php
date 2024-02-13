<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TriggerControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionWithoutPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points/triggers');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexActionWithPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points/triggers/1');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
