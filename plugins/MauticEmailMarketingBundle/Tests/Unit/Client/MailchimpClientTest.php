<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Client;

use MauticPlugin\MauticEmailMarketingBundle\Client\MailchimpClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class MailchimpClientTest extends TestCase
{
    public function testListsAreReadWithTheApiKeyFromItsDataCenter(): void
    {
        $response = new MockResponse('{"lists":[{"id":"a1","name":"Newsletter"},{"id":"b2","name":"Customers"}],"total_items":2}');
        $client   = new MailchimpClient(new MockHttpClient($response));

        $this->assertSame(['a1' => 'Newsletter', 'b2' => 'Customers'], $client->getLists(['username' => 'mautic', 'password' => 'key-us7']));
        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame('https://us7.api.mailchimp.com/3.0/lists?limit=100', $response->getRequestUrl());
        $this->assertContains('Authorization: Basic '.base64_encode('mautic:key-us7'), $response->getRequestOptions()['headers']);
    }

    public function testAnAccountConnectedThroughOauthUsesItsTokenAndEndpoint(): void
    {
        $response = new MockResponse('{"merge_fields":[{"tag":"FNAME","name":"First Name","required":true}]}');
        $client   = new MailchimpClient(new MockHttpClient($response));

        $this->assertSame(
            [
                'FNAME' => ['label' => 'First Name', 'required' => true],
                'EMAIL' => ['label' => 'Email', 'required' => true],
            ],
            $client->getMergeFields(['access_token' => 'token', 'api_endpoint' => 'https://us3.api.mailchimp.com'], 'a1')
        );
        $this->assertSame('https://us3.api.mailchimp.com/3.0/lists/a1/merge-fields', $response->getRequestUrl());
        $this->assertContains('Authorization: OAuth token', $response->getRequestOptions()['headers']);
    }

    public function testSubscribeSendsTheMember(): void
    {
        $response = new MockResponse('{"id":"member"}');
        $client   = new MailchimpClient(new MockHttpClient($response));

        $client->subscribe(['password' => 'key-us7'], 'a1', 'john@example.com', ['FNAME' => 'John'], false, true);

        $this->assertSame('POST', $response->getRequestMethod());
        $this->assertSame('https://us7.api.mailchimp.com/3.0/lists/a1/members', $response->getRequestUrl());
        $this->assertSame(
            [
                'status'        => 'subscribed',
                'send_welcome'  => true,
                'id'            => 'a1',
                'merge_fields'  => ['FNAME' => 'John'],
                'email_address' => 'john@example.com',
            ],
            json_decode($response->getRequestOptions()['body'], true)
        );
    }

    /**
     * @param array<string, mixed> $keys
     */
    #[DataProvider('failureProvider')]
    public function testFailures(array $keys, MockResponse $response, string $message): void
    {
        $client = new MailchimpClient(new MockHttpClient($response));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage($message);

        $client->getLists($keys);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, MockResponse, string}>
     */
    public static function failureProvider(): iterable
    {
        yield 'not configured' => [[], new MockResponse('{}'), 'Mailchimp is not configured.'];
        yield 'key without data center' => [['password' => 'key'], new MockResponse('{}'), 'Invalid key'];
        yield 'error list' => [['password' => 'key-us7'], new MockResponse('{"errors":[{"message":"Bad"},{"message":"request"}]}'), 'Bad request'];
        yield 'problem details' => [['password' => 'key-us7'], new MockResponse('{"title":"API Key Invalid","status":401,"detail":"Your API key may be invalid."}', ['http_code' => 401]), 'Your API key may be invalid.'];
        yield 'transport error' => [['password' => 'key-us7'], new MockResponse('', ['error' => 'Connection refused']), 'Connection refused'];
    }
}
