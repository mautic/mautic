<?php

declare(strict_types=1);

namespace Mautic\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HSTSMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    use ConfigAwareTrait;

    public const PRIORITY = 900;

    protected bool $enableHSTS;

    protected bool $includeDubDomains;

    protected bool $preload;

    protected int $expireTime;

    protected HttpKernelInterface $app;

    public function __construct(HttpKernelInterface $app)
    {
        $this->app               = $app;
        $this->config            = $this->getConfig();
        $this->enableHSTS        = array_key_exists('headers_sts', $this->config) && (bool) $this->config['headers_sts'];
        $this->includeDubDomains = array_key_exists('headers_sts_subdomains', $this->config) && (bool) $this->config['headers_sts_subdomains'];
        $this->preload           = array_key_exists('headers_sts_preload', $this->config) && (bool) $this->config['headers_sts_preload'];
        $this->expireTime        = $this->config['headers_sts_expire_time'] ?? 60;
    }

    public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
    {
        $response = $this->app->handle($request, $type, $catch);

        // Do not include the header in the sub-request response
        if (self::MAIN_REQUEST !== $type) {
            return $response;
        }

        if ($this->enableHSTS && $this->expireTime) {
            $value = 'max-age='.$this->expireTime.($this->includeDubDomains ? '; includeSubDomains' : '').($this->preload ? '; preload' : '');
            $response->headers->set('Strict-Transport-Security', $value);
        }

        return $response;
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }
}
