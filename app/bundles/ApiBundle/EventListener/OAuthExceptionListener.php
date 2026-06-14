<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\EventListener;

use OAuth2\OAuth2ServerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OAuthExceptionListener implements EventSubscriberInterface
{
    private const AUTHORIZE_PATH_PREFIX = '/oauth/v2/authorize';
    private const REDIRECT_URI_MISMATCH = 'redirect_uri_mismatch';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 0]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!str_starts_with($event->getRequest()->getPathInfo(), self::AUTHORIZE_PATH_PREFIX)) {
            return;
        }

        $exception = $event->getThrowable();
        if (!$this->isRedirectUriMismatch($exception)) {
            return;
        }

        $event->setResponse(new JsonResponse(
            [
                'error'             => self::REDIRECT_URI_MISMATCH,
                'error_description' => $this->translator->trans('mautic.api.oauth.error.redirect_uri_mismatch'),
                'action'            => $this->translator->trans('mautic.api.oauth.error.redirect_uri_mismatch.action'),
            ],
            $this->getStatusCode($exception)
        ));
    }

    private function isRedirectUriMismatch(\Throwable $exception): bool
    {
        return $exception instanceof OAuth2ServerException
            && self::REDIRECT_URI_MISMATCH === $exception->getMessage();
    }

    private function getStatusCode(OAuth2ServerException $exception): int
    {
        $statusCode = (int) $exception->getHttpCode();

        return Response::HTTP_INTERNAL_SERVER_ERROR === $statusCode ? Response::HTTP_BAD_REQUEST : $statusCode;
    }
}
