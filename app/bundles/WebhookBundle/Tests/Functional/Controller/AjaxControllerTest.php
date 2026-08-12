<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('database')]
final class AjaxControllerTest extends MauticMysqlTestCase
{
    public function testSendHookTestWithMissingUrl(): void
    {
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=webhook:sendHookTest',
            [
                'url'    => '',
                'secret' => 'test-secret',
                'types'  => ['mautic.lead_post_save_new'],
            ],
            [],
            $this->createAjaxHeaders()
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('html', $content);
        $this->assertStringContainsString('has-error', (string) $content['html']);
        $this->assertStringContainsString('No URL specified', (string) $content['html']);
    }

    public function testSendHookTestWithMissingTypes(): void
    {
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=webhook:sendHookTest',
            [
                'url'    => 'https://example.com/webhook',
                'secret' => 'test-secret',
                'types'  => [],
            ],
            [],
            $this->createAjaxHeaders()
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('html', $content);
        $this->assertStringContainsString('has-error', (string) $content['html']);
        $this->assertStringContainsString('No events selected', (string) $content['html']);
    }

    public function testSendHookTestWithPrivateAddress(): void
    {
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=webhook:sendHookTest',
            [
                'url'    => 'http://localhost/webhook',
                'secret' => 'test-secret',
                'types'  => ['mautic.lead_post_save_new'],
            ],
            [],
            $this->createAjaxHeaders()
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('html', $content);
        $this->assertStringContainsString('has-error', (string) $content['html']);
        $this->assertStringContainsString('private IP address range', (string) $content['html']);
    }

    #[DataProvider('provideInvalidUrls')]
    public function testSendHookTestWithInvalidUrls(string $url, string $expectedError): void
    {
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=webhook:sendHookTest',
            [
                'url'    => $url,
                'secret' => 'test-secret',
                'types'  => ['mautic.lead_post_save_new'],
            ],
            [],
            $this->createAjaxHeaders()
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('html', $content);
        $this->assertStringContainsString('has-error', (string) $content['html']);
        $this->assertStringContainsString($expectedError, (string) $content['html']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidUrls(): iterable
    {
        yield 'empty string' => [
            '',
            'No URL specified',
        ];

        yield 'whitespace only' => [
            '   ',
            'No URL specified',
        ];
    }

    #[DataProvider('provideMissingOrEmptyTypes')]
    public function testSendHookTestWithMissingOrEmptyTypes(mixed $types): void
    {
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=webhook:sendHookTest',
            [
                'url'    => 'https://example.com/webhook',
                'secret' => 'test-secret',
                'types'  => $types,
            ],
            [],
            $this->createAjaxHeaders()
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('html', $content);
        $this->assertStringContainsString('has-error', (string) $content['html']);
        $this->assertStringContainsString('No events selected', (string) $content['html']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideMissingOrEmptyTypes(): iterable
    {
        yield 'empty array' => [[]];
        yield 'null value' => [null];
    }
}
