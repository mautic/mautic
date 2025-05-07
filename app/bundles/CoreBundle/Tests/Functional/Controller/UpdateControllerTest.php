<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class UpdateControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionRendersSuccessfully(): void
    {
        $this->client->request('GET', 's/update');
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
    }

    public function testSchemaActionRendersSuccessfully(): void
    {
        $this->client->request('GET', 's/update/schema');
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
    }
}
