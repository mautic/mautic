<?php

namespace Mautic\Middleware\Tests\Dev;

use Mautic\Middleware\Dev\IpRestrictMiddleware;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class IpRestrictMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    public function testWorkflowWithLocalhostIp(): void
    {
        $inputRequest = new Request();
        $inputRequest->server->set('REMOTE_ADDR', '127.0.0.1'); // 127.0.0.1 is always allowed.
        $httpKernel = new class() implements HttpKernelInterface {
            public function __construct()
            {
            }

            public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
            {
                return new Response();
            }
        };

        $middleware = new IpRestrictMiddleware($httpKernel);
        $response   = $middleware->handle($inputRequest);

        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testWorkflowWithDisallowedIp(): void
    {
        $inputRequest = new Request();
        $inputRequest->server->set('REMOTE_ADDR', 'unallowed.ip.address');
        $httpKernel                 = new class() implements HttpKernelInterface {
            public $handleWasCalled = false;

            public function __construct()
            {
            }

            public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
            {
                $this->handleWasCalled = true;
            }
        };

        $middleware = new IpRestrictMiddleware($httpKernel);
        $response   = $middleware->handle($inputRequest);

        Assert::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        Assert::assertFalse($httpKernel->handleWasCalled);
    }

    public function testWorkflowWithConfiguredIp(): void
    {
        // Remember original custom_dev_hosts value so we could return it afterwards.
        $originalDevHostsValue = $_SERVER['MAUTIC_CUSTOM_DEV_HOSTS'] ?? '[]';

        $_SERVER['MAUTIC_CUSTOM_DEV_HOSTS'] = '["configured.ip.address"]';

        $inputRequest = new Request();
        $inputRequest->server->set('REMOTE_ADDR', 'configured.ip.address');
        $httpKernel = new class($inputRequest) implements HttpKernelInterface {
            public function __construct()
            {
            }

            public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
            {
                return new Response();
            }
        };

        $middleware = new IpRestrictMiddleware($httpKernel);
        $response   = $middleware->handle($inputRequest);

        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode());

        // Set the original value back.
        $_SERVER['MAUTIC_CUSTOM_DEV_HOSTS'] = $originalDevHostsValue;
    }
}
