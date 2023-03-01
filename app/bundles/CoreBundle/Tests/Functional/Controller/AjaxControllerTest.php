<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class AjaxControllerTest extends MauticMysqlTestCase
{
    /**
     * @var MockHandler
     */
    private $clientMockHandler;

    protected function setUp(): void
    {
        $this->configParams['composer_updates'] = 'testUpdateRunChecksAction' !== $this->getName();

        parent::setUp();

        $this->clientMockHandler = self::$container->get(\GuzzleHttp\Handler\MockHandler::class);
    }

    public function testUpdateRunChecksAction(): void
    {
        $responseToPostUpdate  = new Response();
        $responseToGetUpdate   = new Response(200, [], file_get_contents(__DIR__.'/../../Fixtures/releases.json'));
        $responseToGetMetadata = new Response(200, [], file_get_contents(__DIR__.'/../../Fixtures/metadata.json'));

        $this->clientMockHandler->append($responseToPostUpdate, $responseToGetUpdate, $responseToGetMetadata);

        $this->client->request('GET', 's/ajax?action=core:updateRunChecks');
        $response = $this->client->getResponse();
        Assert::assertSame(200, $response->getStatusCode(), $response->getContent());
        Assert::assertStringContainsString('Great! You are running the current version of Mautic.', $response->getContent());
    }

    public function testGlobalSearch(): void
    {
        // Create some entities to search for.
        $contact = new Lead();
        $contact->setFirstName('John8888');

        $page = new Page();
        $page->setTitle('John8888\'s page');
        $page->setAlias('john8888s-page');

        $this->em->persist($contact);
        $this->em->persist($page);
        $this->em->flush();

        // Searching for a string that does not match any entity.
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search=unicorn&tmp=list', [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $content = \json_decode($response->getContent(), true);
        Assert::assertArrayHasKey('newContent', $content);
        Assert::assertSame("<div class=\"panel-group\" id=\"globalSearchPanel\"></div>\n", $content['newContent']);

        // Searching for a string that match 2 entities.
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search=John8888&tmp=list', [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $content = \json_decode($response->getContent(), true);
        Assert::assertArrayHasKey('newContent', $content);
        Assert::assertStringContainsString("/s/contacts/view/{$contact->getId()}", $content['newContent']);
        Assert::assertStringContainsString('John8888', $content['newContent']);
        Assert::assertStringContainsString("/s/pages/view/{$page->getId()}", $content['newContent']);
        Assert::assertStringContainsString('John8888\'s page', $content['newContent']);
    }
}
