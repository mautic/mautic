<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Service;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

class MockHttpClientTest extends MauticMysqlTestCase
{
    public function testHttpClient(): void
    {
        $expectedResponses = [
            function ($method, $url, $options): MockResponse {
                Assert::assertSame(Request::METHOD_GET, $method);
                Assert::assertSame('https://example.com/get', $url);
                $body = '{"get_method": true}';

                return new MockResponse($body);
            },
            function ($method, $url, $options): MockResponse {
                Assert::assertSame(Request::METHOD_POST, $method);
                Assert::assertSame('https://example.com/post', $url);
                $body = '{"post_method": true}';

                return new MockResponse($body);
            },
        ];
        $mockHttpClient = self::getContainer()->get('http_client');
        $mockHttpClient->setResponseFactory($expectedResponses);

        $response = $mockHttpClient->request(Request::METHOD_GET, 'https://example.com/get');
        Assert::assertSame('{"get_method": true}', $response->getContent());

        $mockHttpClient = self::getContainer()->get('http_client');
        $response       = $mockHttpClient->request(Request::METHOD_POST, 'https://example.com/post');
        Assert::assertSame('{"post_method": true}', $response->getContent());
    }
}
