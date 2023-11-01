<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

final class AjaxControllerTest extends MauticMysqlTestCase
{
    /**
     * @var MockHandler
     */
    private $clientMockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientMockHandler = self::$container->get('mautic.http.client.mock_handler');
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
}
