<?php

declare(strict_types=1);

namespace Mautic\Middleware\Tests;

use Mautic\Middleware\CORSMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CORSMiddlewareTest extends TestCase
{
    public function testPageTrackingPostReceivesCorsHeadersWithoutXmlHttpRequestHeader(): void
    {
        $request = Request::create(
            'https://mautic.example/mtc/event',
            Request::METHOD_POST,
            server: ['HTTP_ORIGIN' => 'https://site.example'],
        );

        $response = $this->createMiddleware('mautic_page_tracker_cors')->handle($request);

        $this->assertFalse($request->isXmlHttpRequest());
        $this->assertSame('https://site.example', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
    }

    public function testOtherSimplePostDoesNotReceiveCorsHeaders(): void
    {
        $request = Request::create(
            'https://mautic.example/dwc/example',
            Request::METHOD_POST,
            server: ['HTTP_ORIGIN' => 'https://site.example'],
        );

        $response = $this->createMiddleware('mautic_dynamic_content_public')->handle($request);

        $this->assertFalse($request->isXmlHttpRequest());
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    private function createMiddleware(string $route): CORSMiddleware
    {
        $kernel = new class($route) implements HttpKernelInterface {
            public function __construct(private readonly string $route)
            {
            }

            public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
            {
                $request->attributes->set('_route', $this->route);

                return new Response();
            }
        };

        $middleware = new CORSMiddleware($kernel);

        $reflection = new \ReflectionClass($middleware);
        $reflection->getProperty('restrictCORSDomains')->setValue($middleware, true);
        $reflection->getProperty('validCORSDomains')->setValue($middleware, ['https://site.example']);

        return $middleware;
    }
}
