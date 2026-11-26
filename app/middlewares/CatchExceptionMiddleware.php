<?php

namespace Mautic\Middleware;

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final readonly class CatchExceptionMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    public const int PRIORITY = 100;

    public function __construct(
        private HttpKernelInterface $app
    )
    {
    }

    public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
    {
        $content = 'The site is currently offline due to encountering an error. If the problem persists, please contact the system administrator. System administrators, check server logs for errors.';
        try {
            return $this->app->handle($request, $type, $catch);
        } catch (\Exception $exception) {
            $content = ErrorHandler::getHandler()->handleException($exception, true);
        }

        return new Response($content, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }
}
