<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Ai;

use Mautic\CoreBundle\Ai\OpenAiCompatibleProvider;
use Mautic\CoreBundle\Ai\ProviderLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenAiCompatibleProviderTest extends TestCase
{
    private const string TEXT_MODEL = 'publisher-model:latest';

    private const string EMBEDDING_MODEL = 'vector-model:latest';

    private const string INSTRUCTIONS = 'Answer briefly.';

    private const string PROMPT = 'Describe the campaign.';

    private const array SCHEMA = ['type' => 'object', 'properties' => ['title' => ['type' => 'string']]];

    #[DataProvider('endpoints')]
    public function testSameContractForLocalAndCloud(ProviderLocation $location, string $baseUrl, ?string $apiKey): void
    {
        $chat       = $this->chatResponse('Campaign summary');
        $structured = $this->chatResponse('{"title":"Campaign summary"}');
        $embedding  = new MockResponse('{"object":"list","data":[{"object":"embedding","index":0,"embedding":[0.25,-0.5]}],"usage":{"prompt_tokens":1,"total_tokens":1}}');
        $client     = new MockHttpClient([$chat, $structured, $embedding]);
        $provider  = new OpenAiCompatibleProvider($location, $baseUrl, $client, $apiKey);

        $this->assertSame('Campaign summary', $provider->chat(self::TEXT_MODEL, self::INSTRUCTIONS, self::PROMPT));
        $this->assertSame('Campaign summary', $provider->structured(self::TEXT_MODEL, self::INSTRUCTIONS, self::PROMPT, self::SCHEMA)->title);
        $this->assertSame([0.25, -0.5], $provider->embed(self::EMBEDDING_MODEL, self::PROMPT));

        $this->assertSame(rtrim($baseUrl, '/').'/chat/completions', $chat->getRequestUrl());
        $this->assertSame(rtrim($baseUrl, '/').'/chat/completions', $structured->getRequestUrl());
        $this->assertSame(rtrim($baseUrl, '/').'/embeddings', $embedding->getRequestUrl());
        $this->assertSame(self::TEXT_MODEL, $this->body($chat)['model']);
        $this->assertSame([
            ['role' => 'system', 'content' => self::INSTRUCTIONS],
            ['role' => 'user', 'content' => self::PROMPT],
        ], $this->body($chat)['messages']);
        $this->assertSame(['type' => 'json_schema', 'json_schema' => ['name' => 'mautic_response', 'schema' => self::SCHEMA]], $this->body($structured)['response_format']);
        $this->assertSame(['model' => self::EMBEDDING_MODEL, 'input' => self::PROMPT], $this->body($embedding));

        $options = $chat->getRequestOptions();
        $this->assertSame(0, $options['max_redirects']);
        if (null === $apiKey) {
            $this->assertArrayNotHasKey('authorization', $options['normalized_headers']);
        } else {
            $this->assertSame(['Authorization: Bearer '.$apiKey], $options['normalized_headers']['authorization']);
        }
    }

    /**
     * @return iterable<string, array{ProviderLocation, string, ?string}>
     */
    public static function endpoints(): iterable
    {
        yield 'Ollama on a private host' => [ProviderLocation::Local, 'http://ollama:11434/v1/', null];
        yield 'OpenAI' => [ProviderLocation::Cloud, 'https://api.openai.com/v1', 'test-key'];
    }

    public function testProviderResponseDoesNotLeakIntoErrors(): void
    {
        $client   = new MockHttpClient(new MockResponse('{"error":{"message":"Sensitive provider details"}}', ['http_code' => 401]));
        $provider = new OpenAiCompatibleProvider(ProviderLocation::Cloud, 'https://api.openai.com/v1', $client, 'test-key');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The AI provider could not complete the chat request.');

        $provider->chat(self::TEXT_MODEL, self::INSTRUCTIONS, self::PROMPT);
    }

    public function testStructuredRejectsAJsonArray(): void
    {
        $client   = new MockHttpClient($this->chatResponse('[]'));
        $provider = new OpenAiCompatibleProvider(ProviderLocation::Local, 'http://ollama:11434/v1', $client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The AI provider did not return a JSON object.');

        $provider->structured(self::TEXT_MODEL, self::INSTRUCTIONS, self::PROMPT, self::SCHEMA);
    }

    #[DataProvider('invalidEndpoints')]
    public function testRejectsInvalidEndpoint(ProviderLocation $location, string $baseUrl, ?string $apiKey): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OpenAiCompatibleProvider($location, $baseUrl, new MockHttpClient(), $apiKey);
    }

    /**
     * @return iterable<string, array{ProviderLocation, string, ?string}>
     */
    public static function invalidEndpoints(): iterable
    {
        yield 'cloud without TLS' => [ProviderLocation::Cloud, 'http://example.com/v1', null];
        yield 'key without TLS' => [ProviderLocation::Local, 'http://ollama:11434/v1', 'test-key'];
        yield 'URL with user info' => [ProviderLocation::Cloud, 'https://test@example.com/v1', null];
        yield 'non HTTP' => [ProviderLocation::Local, 'ftp://example.com/v1', null];
    }

    private function chatResponse(string $text): MockResponse
    {
        return new MockResponse(json_encode([
            'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => $text], 'finish_reason' => 'stop']],
            'usage'   => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
        ], \JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function body(MockResponse $response): array
    {
        $body = $response->getRequestOptions()['body'];
        $this->assertIsString($body);
        /** @var array<string, mixed> $payload */
        $payload = json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);

        return $payload;
    }
}
