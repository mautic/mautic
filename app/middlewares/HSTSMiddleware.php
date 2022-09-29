<?php

declare(strict_types=1);

namespace Mautic\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HSTSMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    use ConfigAwareTrait;

    const PRIORITY = 900;

    protected bool $enableHSTS;
    protected bool $includeDubDomains;
    protected int $expireTime;
    protected HttpKernelInterface $app;

    public function __construct(HttpKernelInterface $app)
    {
        $this->app               = $app;
        $this->config            = $this->getConfig();
        $this->enableHSTS        = array_key_exists('headers_sts', $this->config) && (bool) $this->config['headers_sts'];
        $this->includeDubDomains = array_key_exists('headers_sts_subdomains', $this->config) && (bool) $this->config['headers_sts_subdomains'];
        $this->expireTime        = $this->config['headers_sts_expire_time'] ?? 60;
    }

    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true): Response
    {
        $response = $this->app->handle($request, $type, $catch);

        //Do not include the header in the sub-request response
        if (self::MASTER_REQUEST !== $type) {
            return $response;
        }

        if ($this->enableHSTS && $this->expireTime) {
            $value = 'max-age='.$this->expireTime.($this->includeDubDomains ? '; includeSubDomains' : '');
            $response->headers->set('Strict-Transport-Security', $value);
        }

        return $response;
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }
}
