<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testMtcEventCompanyXss(): void
    {
        $this->client->request('POST', '/mtc/event', [
            'page_url' => 'https://example.com?Company=%3Cimg+src+onerror%3Dalert%28%27Company%27%29%3E',
        ]);
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($clientResponse->isOk());

        $response = json_decode($clientResponse->getContent(), true);

        $this->client->request('GET', sprintf('/s/contacts/view/%d', $response['id']));
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($clientResponse->isOk());
        $content = $clientResponse->getContent();

        Assert::assertStringNotContainsString('<img src onerror=alert(\'Company\')>', $content);

        $crawler        = $this->client->request('GET', sprintf('/s/contacts/edit/%d', $response['id']));
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($clientResponse->isOk());
        $content = $clientResponse->getContent();

        Assert::assertStringNotContainsString('<img src onerror=alert(\'Company\')>', $content);

        $buttonCrawlerNode = $crawler->selectButton('Save & Close');
        $form              = $buttonCrawlerNode->form();
        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($clientResponse->isOk());
        $content = $clientResponse->getContent();
        Assert::assertStringNotContainsString('<img src onerror=alert(\'Company\')>', $content);
    }
}
