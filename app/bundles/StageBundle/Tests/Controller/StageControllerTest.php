<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\StageBundle\Tests\_helpers\StageTestSeederTrait;

final class StageControllerTest extends MauticMysqlTestCase
{
    use StageTestSeederTrait;

    public function testIndexDisplaysContactCounts(): void
    {
        $this->seedStagesWithLeads();

        $this->client->request('GET', '/s/stages');
        $response = $this->client->getResponse();

        self::assertResponseIsSuccessful();
        $content = $response->getContent();
        self::assertStringContainsString('View 2 Contacts', $content);
        self::assertStringContainsString('No Contacts', $content);
    }
}
