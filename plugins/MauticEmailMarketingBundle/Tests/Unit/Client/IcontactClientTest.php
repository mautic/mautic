<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Client;

use MauticPlugin\MauticEmailMarketingBundle\Client\IcontactClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IcontactClientTest extends TestCase
{
    private const array KEYS = ['API-AppId' => 'app', 'API-Username' => 'user', 'API-Password' => 'secret'];

    public function testSavedAccountAndFolderAreUsed(): void
    {
        $response = new MockResponse('{"lists":[{"listId":"7","name":"Newsletter"}]}');
        $client   = new IcontactClient(new MockHttpClient($response));

        $this->assertSame(['7' => 'Newsletter'], $client->getLists(self::KEYS + ['accountId' => '1', 'clientFolderId' => '2']));
        $this->assertSame('https://app.icontact.com/icp/a/1/c/2/lists', $response->getRequestUrl());
        $this->assertContains('Api-AppId: app', $response->getRequestOptions()['headers']);
        $this->assertContains('API-Password: secret', $response->getRequestOptions()['headers']);
        $this->assertContains('Api-Version: 2.2', $response->getRequestOptions()['headers']);
    }

    public function testAccountAndFolderAreLookedUpOnceWhenNotSaved(): void
    {
        $responses = [
            new MockResponse('{"accounts":[{"accountId":"1"}]}'),
            new MockResponse('{"clientfolders":[{"clientFolderId":"2"}]}'),
            new MockResponse('{"customfields":[{"customFieldId":"color","publicName":"Color"}]}'),
            new MockResponse('{"lists":[]}'),
        ];
        $httpClient = new MockHttpClient($responses);
        $client     = new IcontactClient($httpClient);

        $this->assertSame(['color' => 'Color'], $client->getCustomFields(self::KEYS));
        $this->assertSame([], $client->getLists(self::KEYS));

        $this->assertSame(4, $httpClient->getRequestsCount());
        $this->assertSame('https://app.icontact.com/icp/a', $responses[0]->getRequestUrl());
        $this->assertSame('https://app.icontact.com/icp/a/1/c', $responses[1]->getRequestUrl());
        $this->assertSame('https://app.icontact.com/icp/a/1/c/2/customfields', $responses[2]->getRequestUrl());
        $this->assertSame('https://app.icontact.com/icp/a/1/c/2/lists', $responses[3]->getRequestUrl());
    }

    public function testSubscribeCreatesTheContactThenTheSubscription(): void
    {
        $responses = [
            new MockResponse('{"contacts":[{"contactId":"99"}]}'),
            new MockResponse('{"subscriptions":[]}'),
        ];
        $client = new IcontactClient(new MockHttpClient($responses));

        $client->subscribe(self::KEYS + ['accountId' => '1', 'clientFolderId' => '2'], '7', ['email' => 'john@example.com']);

        $this->assertSame('https://app.icontact.com/icp/a/1/c/2/contacts', $responses[0]->getRequestUrl());
        $this->assertSame([['email' => 'john@example.com', 'status' => 'normal']], json_decode($responses[0]->getRequestOptions()['body'], true));
        $this->assertSame('https://app.icontact.com/icp/a/1/c/2/subscriptions', $responses[1]->getRequestUrl());
        $this->assertSame([['status' => 'normal', 'listId' => '7', 'contactId' => '99']], json_decode($responses[1]->getRequestOptions()['body'], true));
    }

    public function testErrorsAreReported(): void
    {
        $client = new IcontactClient(new MockHttpClient(new MockResponse('{"errors":["Invalid AppId"]}', ['http_code' => 401])));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid AppId');

        $client->getLists(self::KEYS + ['accountId' => '1', 'clientFolderId' => '2']);
    }

    #[DataProvider('failureProvider')]
    public function testFailures(MockResponse $first, ?MockResponse $second, string $message): void
    {
        $client = new IcontactClient(new MockHttpClient(array_filter([$first, $second])));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage($message);

        $client->getLists(self::KEYS);
    }

    /**
     * @return iterable<string, array{MockResponse, ?MockResponse, string}>
     */
    public static function failureProvider(): iterable
    {
        yield 'no account' => [new MockResponse('{"accounts":[]}'), null, 'No iContact account found for these credentials.'];
        yield 'no client folder' => [new MockResponse('{"accounts":[{"accountId":"1"}]}'), new MockResponse('{"clientfolders":[]}'), 'No iContact client folder found for these credentials.'];
        yield 'error object' => [new MockResponse('{"errors":{"message":"Timeout"}}'), null, 'Timeout'];
        yield 'structured error' => [new MockResponse('{"errors":[{"code":"E1","message":"Bad folder"}]}'), null, '{"code":"E1","message":"Bad folder"}'];
        yield 'HTTP error without details' => [new MockResponse('{}', ['http_code' => 503]), null, 'iContact returned HTTP 503.'];
        yield 'transport error' => [new MockResponse('', ['error' => 'Connection refused']), null, 'Connection refused'];
    }

    public function testRequiresAllCredentials(): void
    {
        $client = new IcontactClient(new MockHttpClient());

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('iContact is not configured.');

        $client->getLists(['API-AppId' => 'app']);
    }
}
