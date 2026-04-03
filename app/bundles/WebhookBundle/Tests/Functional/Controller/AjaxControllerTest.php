<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        Assert::assertIsArray($content);
        Assert::assertArrayHasKey('html', $content);
        Assert::assertStringContainsString('has-error', $content['html']);
        Assert::assertStringContainsString('No URL specified', $content['html']);
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
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        Assert::assertIsArray($content);
        Assert::assertArrayHasKey('html', $content);
        Assert::assertStringContainsString('has-error', $content['html']);
        Assert::assertStringContainsString('No events selected', $content['html']);
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
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        Assert::assertIsArray($content);
        Assert::assertArrayHasKey('html', $content);
        Assert::assertStringContainsString('has-error', $content['html']);
        Assert::assertStringContainsString('private IP address range', $content['html']);
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
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        Assert::assertIsArray($content);
        Assert::assertArrayHasKey('html', $content);
        Assert::assertStringContainsString('has-error', $content['html']);
        Assert::assertStringContainsString($expectedError, $content['html']);
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
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        Assert::assertIsArray($content);
        Assert::assertArrayHasKey('html', $content);
        Assert::assertStringContainsString('has-error', $content['html']);
        Assert::assertStringContainsString('No events selected', $content['html']);
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
