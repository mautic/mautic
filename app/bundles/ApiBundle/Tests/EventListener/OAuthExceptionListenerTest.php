<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\EventListener;

use Mautic\ApiBundle\EventListener\OAuthExceptionListener;
use OAuth2\OAuth2ServerException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OAuthExceptionListenerTest extends TestCase
{
    public function testSubscribesToKernelException(): void
    {
        self::assertSame(
            [KernelEvents::EXCEPTION => ['onKernelException', 0]],
            OAuthExceptionListener::getSubscribedEvents()
        );
    }

    public function testReturnsBadRequestWithActionForRedirectUriMismatch(): void
    {
        $event = $this->createExceptionEvent(
            Request::create('/oauth/v2/authorize', 'GET'),
            new OAuth2ServerException(
                Response::HTTP_BAD_REQUEST,
                'redirect_uri_mismatch',
                'The redirect URI provided does not match registered URI(s).'
            )
        );

        (new OAuthExceptionListener($this->createTranslator()))->onKernelException($event);

        self::assertNotNull($event->getResponse());
        self::assertSame(Response::HTTP_BAD_REQUEST, $event->getResponse()->getStatusCode());

        $payload = json_decode((string) $event->getResponse()->getContent(), true);

        self::assertSame('redirect_uri_mismatch', $payload['error']);
        self::assertSame(
            'The OAuth redirect URI does not match the API credentials configuration.',
            $payload['error_description']
        );
        self::assertSame('Change it in the settings.', $payload['action']);
    }

    public function testConverts500TooBadRequestForRedirectUriMismatch(): void
    {
        $event = $this->createExceptionEvent(
            Request::create('/oauth/v2/authorize', 'GET'),
            new OAuth2ServerException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'redirect_uri_mismatch',
                'The redirect URI provided does not match registered URI(s).'
            )
        );

        (new OAuthExceptionListener($this->createTranslator()))->onKernelException($event);

        self::assertNotNull($event->getResponse());
        self::assertSame(Response::HTTP_BAD_REQUEST, $event->getResponse()->getStatusCode());
    }

    public function testIgnoresNonAuthorizeRoutes(): void
    {
        $event = $this->createExceptionEvent(
            Request::create('/api/contacts', 'GET'),
            new OAuth2ServerException(Response::HTTP_BAD_REQUEST, 'redirect_uri_mismatch')
        );

        (new OAuthExceptionListener($this->createTranslator()))->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testIgnoresOtherOauthErrors(): void
    {
        $event = $this->createExceptionEvent(
            Request::create('/oauth/v2/authorize', 'GET'),
            new OAuth2ServerException(Response::HTTP_BAD_REQUEST, 'invalid_request')
        );

        (new OAuthExceptionListener($this->createTranslator()))->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testIgnoresNonOauthExceptions(): void
    {
        $event = $this->createExceptionEvent(
            Request::create('/oauth/v2/authorize', 'GET'),
            new \RuntimeException('Something went wrong')
        );

        (new OAuthExceptionListener($this->createTranslator()))->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    private function createExceptionEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    private function createTranslator(): TranslatorInterface
    {
        return new class implements TranslatorInterface {
            /**
             * @var array<string, string>
             */
            private const TRANSLATIONS = [
                'mautic.api.oauth.error.redirect_uri_mismatch'        => 'The OAuth redirect URI does not match the API credentials configuration.',
                'mautic.api.oauth.error.redirect_uri_mismatch.action' => 'Change it in the settings.',
            ];

            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return self::TRANSLATIONS[$id] ?? $id;
            }

            public function getLocale(): string
            {
                return 'en_US';
            }
        };
    }
}
