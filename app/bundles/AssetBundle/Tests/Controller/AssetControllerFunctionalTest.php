<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class AssetControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Index action should return status code 200.
     */
    public function testIndexAction(): void
    {
        $this->client->request('GET', '/s/assets');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
    }
}
