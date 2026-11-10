<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Client;

use MauticPlugin\MauticEmailMarketingBundle\Client\ConstantContactClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ConstantContactClientTest extends TestCase
{
    private const array KEYS = ['client_id' => 'api-key', 'access_token' => 'token'];

    public function testListsAreReadWithTheApiKeyAndToken(): void
    {
        $response = new MockResponse('[{"id":"1","name":"Newsletter"}]');
        $client   = new ConstantContactClient(new MockHttpClient($response));

        $this->assertSame(['1' => 'Newsletter'], $client->getLists(self::KEYS));
        $this->assertSame('https://api.constantcontact.com/v2/lists?api_key=api-key', $response->getRequestUrl());
        $this->assertContains('Authorization: Bearer token', $response->getRequestOptions()['headers']);
    }

    public function testSubscribeSendsTheContactToTheList(): void
    {
        $response = new MockResponse('{"id":"contact"}', ['http_code' => 201]);
        $client   = new ConstantContactClient(new MockHttpClient($response));

        $client->subscribe(self::KEYS, '1', 'john@example.com', ['first_name' => 'John'], 'ACTION_BY_VISITOR');

        $this->assertSame('POST', $response->getRequestMethod());
        $this->assertSame('https://api.constantcontact.com/v2/contacts?api_key=api-key&action_by=ACTION_BY_VISITOR', $response->getRequestUrl());
        $this->assertSame(
            [
                'first_name'      => 'John',
                'lists'           => [['id' => '1']],
                'email_addresses' => [['email_address' => 'john@example.com']],
            ],
            json_decode($response->getRequestOptions()['body'], true)
        );
    }

    public function testErrorMessagesAreReported(): void
    {
        $client = new ConstantContactClient(new MockHttpClient(new MockResponse('[{"error_key":"a","error_message":"Email exists."}]', ['http_code' => 409])));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Email exists.');

        $client->subscribe(self::KEYS, '1', 'john@example.com', [], 'ACTION_BY_OWNER');
    }

    public function testRequiresTheApiKeyAndToken(): void
    {
        $client = new ConstantContactClient(new MockHttpClient());

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Constant Contact is not configured.');

        $client->getLists(['client_id' => 'api-key']);
    }
}
