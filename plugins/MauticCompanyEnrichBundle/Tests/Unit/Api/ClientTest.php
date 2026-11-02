<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Tests\Unit\Api;

use MauticPlugin\MauticCompanyEnrichBundle\Api\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ClientTest extends TestCase
{
    #[DataProvider('supportedRequests')]
    public function testAllResearchOperationsHaveWorkingRequestShapes(string $operation, array $query, array $body, string $method, string $path): void
    {
        $calls = 0;
        $client = new Client(new MockHttpClient(function ($actualMethod, $url, array $options) use (&$calls, $method, $path, $body): MockResponse {
            ++$calls;
            $this->assertSame($method, $actualMethod);
            $this->assertSame($path, parse_url($url, PHP_URL_PATH));
            if ('POST' === $method) {
                $this->assertSame($body, json_decode($options['body'], true));
            }

            return new MockResponse('{"items":[],"creditsConsumed":0}');
        }));
        $client->query('fixture-key', $operation, $query, $body);
        $this->assertSame(1, $calls);
    }

    public static function supportedRequests(): iterable
    {
        yield 'domain' => ['enrich_company', ['domain' => 'example.com'], [], 'GET', '/companies/enrich'];
        yield 'properties' => ['enrich_company_properties', [], ['name' => 'Example'], 'POST', '/companies/enrich'];
        yield 'batch' => ['enrich_companies_batch', [], ['domains' => ['example.com']], 'POST', '/companies/enrich/batch'];
        yield 'company' => ['get_company', ['id' => '00000000-0000-4000-8000-000000000001'], [], 'GET', '/companies'];
        yield 'company search' => ['search_companies', [], ['semanticQuery' => 'Accounting software', 'pageSize' => 10], 'POST', '/companies/search'];
        yield 'company count' => ['count_companies', [], ['semanticQuery' => 'Accounting software'], 'POST', '/companies/search/count'];
        yield 'similar' => ['similar_companies', [], ['domains' => ['example.com'], 'pageSize' => 10], 'POST', '/companies/similar'];
        yield 'similar count' => ['count_similar', [], ['domains' => ['example.com']], 'POST', '/companies/similar/count'];
        yield 'workforce' => ['workforce', ['domain' => 'example.com'], [], 'GET', '/companies/workforce'];
        yield 'lookup person' => ['lookup_person', [], ['email' => 'alex@example.com'], 'POST', '/people/lookup'];
        yield 'get person' => ['get_person', ['id' => '9007199254740993'], [], 'GET', '/people'];
        yield 'people search' => ['search_people', [], ['domains' => ['example.com'], 'pageSize' => 10], 'POST', '/people/search'];
        yield 'education' => ['person_education', ['id' => '9007199254740993'], [], 'GET', '/people/education'];
        yield 'autocomplete' => ['autocomplete_companies', ['query' => 'Example'], [], 'GET', '/companies/autocomplete'];
        yield 'company preview' => ['preview_companies', [], ['semanticQuery' => 'Accounting software'], 'POST', '/companies/search/preview'];
        yield 'similar preview' => ['preview_similar', [], ['domains' => ['example.com']], 'POST', '/companies/similar/preview'];
    }

    public function testDomainLookupIsBoundedAndDoesNotRedirectOrWait(): void
    {
        $calls = 0;
        $client = new Client(new MockHttpClient(function ($method, $url, array $options) use (&$calls): MockResponse {
            ++$calls;
            $this->assertSame('GET', $method);
            $this->assertSame('https://api.companyenrich.com/companies/enrich?domain=example.com&expand=workforce&waitForEnrichment=false', $url);
            $this->assertSame(0, $options['max_redirects']);
            $this->assertEqualsWithDelta(30.0, $options['timeout'], PHP_FLOAT_EPSILON);
            $this->assertContains('Authorization: Bearer fixture-key', $options['headers']);

            return new MockResponse('{"id":"company-id","name":"Example"}', ['response_headers' => ['x-credit-cost: 1']]);
        }));
        $result = $client->query('fixture-key', 'enrich_company', ['domain' => 'https://EXAMPLE.com/about', 'expand' => ['workforce']]);
        $this->assertSame(1, $calls);
        $this->assertEqualsWithDelta(1.0, $result['credits_consumed'], PHP_FLOAT_EPSILON);
        $this->assertSame('Example', $result['data']['name']);
    }

    public function testSearchPreservesMetadataZeroCreditsAndFullIdentifiers(): void
    {
        $client = new Client(new MockHttpClient(function ($method, $url, array $options): MockResponse {
            $this->assertSame('POST', $method);
            $this->assertSame('https://api.companyenrich.com/people/search', $url);
            $this->assertSame(['page' => 1, 'pageSize' => 10], json_decode($options['body'], true));

            return new MockResponse('{"items":[{"id":9007199254740993}],"totalCount":1,"creditsConsumed":0,"page":1}');
        }));
        $result = $client->query('fixture-key', 'search_people', [], ['page' => 1, 'pageSize' => 10]);
        $this->assertSame(9007199254740993, $result['data']['items'][0]['id']);
        $this->assertEqualsWithDelta(0.0, $result['credits_consumed'], PHP_FLOAT_EPSILON);
        $this->assertSame(1, $result['data']['totalCount']);
    }

    #[DataProvider('invalidRequests')]
    public function testInvalidInputMakesNoHttpRequest(string $operation, array $query, array $body): void
    {
        $client = new Client(new MockHttpClient(function (): never { self::fail('Invalid input must not spend credits.'); }));
        $this->expectException(\InvalidArgumentException::class);
        $client->query('fixture-key', $operation, $query, $body);
    }

    public static function invalidRequests(): iterable
    {
        yield 'unknown operation' => ['delete_list', [], []];
        yield 'missing domain' => ['enrich_company', [], []];
        yield 'bad domain' => ['enrich_company', ['domain' => 'https://u:p@example.com'], []];
        yield 'unknown query' => ['enrich_company', ['domain' => 'example.com', 'apiKey' => 'bad'], []];
        yield 'page too large' => ['search_companies', [], ['pageSize' => 101]];
        yield 'page boolean' => ['search_people', [], ['pageSize' => true]];
        yield 'empty properties' => ['enrich_company_properties', [], []];
        yield 'blank properties' => ['enrich_company_properties', [], ['name' => '  ']];
        yield 'invalid email' => ['lookup_person', [], ['email' => 'not-an-email']];
        yield 'invalid company ID' => ['get_company', ['id' => 'not-a-uuid'], []];
        yield 'invalid social URL' => ['enrich_company_properties', [], ['linkedinUrl' => 'not-a-url']];
        yield 'empty workforce' => ['workforce', [], []];
        yield 'batch limit' => ['enrich_companies_batch', [], ['domains' => array_fill(0, 51, 'example.com')]];
        yield 'lookalike limit' => ['similar_companies', [], ['domains' => array_fill(0, 11, 'example.com')]];
    }

    #[DataProvider('providerErrors')]
    public function testProviderErrorsAreRedactedAndNotRetried(int $status): void
    {
        $calls = 0;
        $client = new Client(new MockHttpClient(function () use (&$calls, $status): MockResponse {
            ++$calls;

            return new MockResponse('secret fixture-key https://private.example', ['http_code' => $status]);
        }));
        try {
            $client->query('fixture-key', 'enrich_company', ['domain' => 'example.com']);
            self::fail('Expected provider error.');
        } catch (\RuntimeException $error) {
            $this->assertStringNotContainsString('fixture-key', $error->getMessage());
            $this->assertStringNotContainsString('private.example', $error->getMessage());
            $this->assertSame(1, $calls);
        }
    }

    public static function providerErrors(): iterable
    {
        foreach ([301, 400, 401, 402, 404, 422, 429, 500] as $status) {
            yield (string) $status => [$status];
        }
    }

    public function testMalformedResponseDoesNotExposeItsBody(): void
    {
        $client = new Client(new MockHttpClient(new MockResponse('fixture-key not JSON')));
        $this->expectExceptionMessage('CompanyEnrich returned an invalid JSON response.');
        $client->query('fixture-key', 'enrich_company', ['domain' => 'example.com']);
    }

    public function testOversizedResponseIsStopped(): void
    {
        $client = new Client(new MockHttpClient(new MockResponse(str_repeat('x', 4 * 1024 * 1024 + 1))));
        $this->expectExceptionMessage('CompanyEnrich response is too large.');
        $client->query('fixture-key', 'enrich_company', ['domain' => 'example.com']);
    }
}
