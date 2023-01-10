<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class FileManagerControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testAssetsAction(): void
    {
        $this->client->request('GET', '/s/grapesjsbuilder/assets');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent(), true);
        $this->assertNotEmpty($content['data']);
    }
}
