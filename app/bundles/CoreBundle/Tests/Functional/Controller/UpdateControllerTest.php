<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

final class UpdateControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionRendersSuccessfully(): void
    {
        $this->client->request('GET', 's/update');
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSchemaActionRendersSuccessfully(): void
    {
        $this->client->request('GET', 's/update/schema');
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
