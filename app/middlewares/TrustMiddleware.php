<?php

namespace Mautic\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class TrustMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    use ConfigAwareTrait;

    public const int PRIORITY = 0;

    public function __construct(
        private HttpKernelInterface $app
    )
    {
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
    {
        $config = $this->getConfig();

        if (!empty($config['trusted_proxies'])) {
            Request::setTrustedProxies($config['trusted_proxies'], Request::getTrustedHeaderSet());
        }

        if (!empty($config['trusted_hosts'])) {
            Request::setTrustedHosts($config['trusted_hosts']);
        }

        return $this->app->handle($request, $type, $catch);
    }
}
