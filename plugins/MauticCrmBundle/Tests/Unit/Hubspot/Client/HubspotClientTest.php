<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\Client;

use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Hubspot\Exception\HubspotApiException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HubspotClientTest extends TestCase
{
    /**
     * @var list<array{method: string, url: string, headers: list<string>, body: mixed}>
     */
    private array $requests = [];

    public function testGetPropertiesSendsTheAccessToken(): void
    {
        $client = $this->client([new MockResponse(json_encode(['results' => [['name' => 'email', 'label' => 'Email', 'type' => 'string']]]))]);

        $this->assertSame([['name' => 'email', 'label' => 'Email', 'type' => 'string']], $client->getProperties(HubspotClient::CONTACTS));
        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame('https://api.hubapi.com/crm/v3/properties/contacts', $this->requests[0]['url']);
        $this->assertContains('Authorization: Bearer pat-token', $this->requests[0]['headers']);
    }

    public function testSearchModifiedFiltersAndSortsByTheLastModificationDate(): void
    {
        $client = $this->client([new MockResponse(json_encode([
            'results' => [['id' => '1', 'properties' => ['email' => 'john@example.com']]],
            'paging'  => ['next' => ['after' => '100']],
        ]))]);

        $page = $client->searchModified(
            HubspotClient::COMPANIES,
            ['name'],
            new \DateTimeImmutable('2026-11-01T00:00:00Z'),
            new \DateTimeImmutable('2026-11-02T00:00:00Z'),
            '100'
        );

        $this->assertSame(['results' => [['id' => '1', 'properties' => ['email' => 'john@example.com']]], 'after' => '100'], $page);
        $this->assertSame('https://api.hubapi.com/crm/v3/objects/companies/search', $this->requests[0]['url']);
        $this->assertSame([
            'filterGroups' => [['filters' => [
                ['propertyName' => 'hs_lastmodifieddate', 'operator' => 'GTE', 'value' => '1793491200000'],
                ['propertyName' => 'hs_lastmodifieddate', 'operator' => 'LTE', 'value' => '1793577600000'],
            ]]],
            'sorts'        => [['propertyName' => 'hs_lastmodifieddate', 'direction' => 'ASCENDING']],
            'properties'   => ['name', 'hs_lastmodifieddate'],
            'limit'        => 100,
            'after'        => '100',
        ], $this->requests[0]['body']);
    }

    public function testSearchModifiedWithoutAFollowingPage(): void
    {
        $client = $this->client([new MockResponse(json_encode(['results' => []]))]);

        $this->assertSame(['results' => [], 'after' => null], $client->searchModified(HubspotClient::CONTACTS, [], null, null));
        $this->assertSame([], $this->requests[0]['body']['filterGroups']);
    }

    public function testUpsertContactsByEmailCollectsResultsAndErrors(): void
    {
        $client = $this->client([new MockResponse(json_encode([
            'status'  => 'COMPLETE',
            'results' => [['id' => '7', 'properties' => ['email' => 'john@example.com']]],
            'errors'  => [['message' => 'Property values were not valid']],
        ]), ['http_code' => 207])]);

        $response = $client->upsertContactsByEmail(['john@example.com' => ['firstname' => 'John']]);

        $this->assertSame([['id' => '7', 'properties' => ['email' => 'john@example.com']]], $response['results']);
        $this->assertSame([['message' => 'Property values were not valid']], $response['errors']);
        $this->assertSame('https://api.hubapi.com/crm/v3/objects/contacts/batch/upsert', $this->requests[0]['url']);
        $this->assertSame(['inputs' => [['idProperty' => 'email', 'id' => 'john@example.com', 'properties' => ['firstname' => 'John']]]], $this->requests[0]['body']);
    }

    public function testBatchesAreSplitAtOneHundredRecords(): void
    {
        $propertiesById = [];
        for ($id = 1; $id <= 150; ++$id) {
            $propertiesById[(string) $id] = ['name' => 'Company '.$id];
        }

        $client = $this->client([
            new MockResponse(json_encode(['results' => [['id' => '1']]])),
            new MockResponse(json_encode(['results' => [['id' => '101']]])),
        ]);

        $this->assertSame([['id' => '1'], ['id' => '101']], $client->update(HubspotClient::COMPANIES, $propertiesById)['results']);
        $this->assertCount(100, $this->requests[0]['body']['inputs']);
        $this->assertCount(50, $this->requests[1]['body']['inputs']);
    }

    public function testErrorResponseThrowsWithTheStatusCode(): void
    {
        $client = $this->client([new MockResponse(json_encode(['message' => 'You have reached your secondly limit.']), ['http_code' => 429])]);

        try {
            $client->create(HubspotClient::COMPANIES, ['name' => 'Acme']);
            $this->fail('No exception thrown');
        } catch (HubspotApiException $e) {
            $this->assertSame('You have reached your secondly limit.', $e->getMessage());
            $this->assertSame(429, $e->getCode());
            $this->assertTrue($e->isRateLimited());
            $this->assertTrue($e->isTemporary());
        }
    }

    public function testMissingTokenThrowsWithoutARequest(): void
    {
        $client = $this->client([], null);

        $this->expectException(HubspotApiException::class);

        try {
            $client->getProperties(HubspotClient::CONTACTS);
        } finally {
            $this->assertSame([], $this->requests);
        }
    }

    /**
     * @param MockResponse[] $responses
     */
    private function client(array $responses, ?string $token = 'pat-token'): HubspotClient
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $this->requests[] = [
                'method'  => $method,
                'url'     => $url,
                'headers' => $options['headers'] ?? [],
                'body'    => isset($options['body']) && '' !== $options['body'] ? json_decode((string) $options['body'], true) : null,
            ];

            return array_shift($responses) ?? new MockResponse('', ['http_code' => 500]);
        });

        $config = $this->createStub(Config::class);
        $config->method('getAccessToken')->willReturn($token);

        return new HubspotClient($httpClient, $config);
    }
}
