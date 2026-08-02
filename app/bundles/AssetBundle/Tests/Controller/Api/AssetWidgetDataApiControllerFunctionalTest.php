<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AssetWidgetDataApiControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function assetWidgetTypesProvider(): iterable
    {
        yield 'downloads-in-time' => ['asset.downloads.in.time'];
        yield 'unique-vs-repetitive' => ['unique.vs.repetitive.downloads'];
        yield 'popular-assets' => ['popular.assets'];
        yield 'created-assets' => ['created.assets'];
    }

    #[DataProvider('assetWidgetTypesProvider')]
    public function testAssetWidgetDataEndpointReturnsNonEmptyDataForApiLibraryShape(string $type): void
    {
        $this->client->request('GET', '/api/data/'.$type);
        $response = $this->client->getResponse();

        $this->assertNotSame(
            404,
            $response->getStatusCode(),
            'Unexpected 404 for widget "'.$type.'". Body: '.$response->getContent()
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertIsArray($payload, 'Invalid JSON payload for widget "'.$type.'".');
        $this->assertArrayHasKey('data', $payload, 'Missing data key for widget "'.$type.'".');
        $this->assertNotEmpty($payload['data'], 'Data payload is empty for widget "'.$type.'". Full payload: '.$response->getContent());
    }
}
