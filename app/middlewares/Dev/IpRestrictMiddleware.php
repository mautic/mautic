<?php

namespace Mautic\Middleware\Dev;

use Mautic\Middleware\ConfigAwareTrait;
use Mautic\Middleware\PrioritizedMiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class IpRestrictMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    use ConfigAwareTrait;

    public const PRIORITY = 20;

    /**
     * @var HttpKernelInterface
     */
    protected $app;

    /**
     * @var array
     */
    protected $allowedIps;

    public function __construct(HttpKernelInterface $app)
    {
        $this->app        = $app;
        $this->allowedIps = ['127.0.0.1', 'fe80::1', '::1'];

        $parameters = $this->getConfig();
        if (array_key_exists('dev_hosts', $parameters) && is_array($parameters['dev_hosts'])) {
            $this->allowedIps = array_merge($this->allowedIps, $parameters['dev_hosts']);
        }

        if (isset($_SERVER['MAUTIC_CUSTOM_DEV_HOSTS'])) {
            $localIps         = json_decode($_SERVER['MAUTIC_CUSTOM_DEV_HOSTS'], true);
            $this->allowedIps = array_merge($this->allowedIps, $localIps);
        }
    }

    /**
     * This check prevents access to debug front controllers
     * that are deployed by accident to production servers.
     *
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
    {
        if (in_array($request->getClientIp(), $this->allowedIps) || false !== getenv('DDEV_TLD')) {
            return $this->app->handle($request, $type, $catch);
        }

        return new Response('You are not allowed to access this file.', 403);
    }

    public function getPriority()
    {
        return self::PRIORITY;
    }
}
