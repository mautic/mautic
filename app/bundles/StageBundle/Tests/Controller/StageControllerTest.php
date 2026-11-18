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
        [$stageWithContacts, $stageWithoutContacts] = $this->seedStagesWithLeads();

        $this->client->request('GET', '/s/stages');

        $this->assertResponseIsSuccessful();

        $crawler = $this->client->getCrawler();
        $stageWithContactsCount = $crawler->filter('#stage-contacts-count-'.$stageWithContacts->getId());
        $this->assertSame('a', $stageWithContactsCount->nodeName());
        $this->assertStringContainsString('2', $stageWithContactsCount->text());
        $this->assertStringContainsString('/s/contacts', (string) $stageWithContactsCount->attr('href'));

        $stageWithoutContactsCount = $crawler->filter('#stage-contacts-count-'.$stageWithoutContacts->getId());
        $this->assertSame('div', $stageWithoutContactsCount->nodeName());
        $this->assertNull($stageWithoutContactsCount->attr('href'));
    }
}
