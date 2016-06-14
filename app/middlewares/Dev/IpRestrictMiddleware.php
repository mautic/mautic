<?php

/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Middleware\Dev;

use Mautic\Middleware\PrioritizedMiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class IpRestrictMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    const PRIORITY = 20;

    /**
     * @var HttpKernelInterface
     */
    protected $app;

    /**
     * @var array
     */
    protected $allowedIps;

    /**
     * CatchExceptionMiddleware constructor.
     *
     * @param HttpKernelInterface $app
     */
    public function __construct(HttpKernelInterface $app)
    {
        $this->app = $app;
        $this->allowedIps = ['127.0.0.1', 'fe80::1', '::1'];

        if (isset($_SERVER['MAUTIC_DEV_HOSTS'])) {
            $localIps         = explode(' ', $_SERVER['MAUTIC_DEV_HOSTS']);
            $this->allowedIps = array_merge($this->allowedIps, $localIps);
        }
    }

    /**
     * This check prevents access to debug front controllers
     * that are deployed by accident to production servers.
     *
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if (in_array($request->getClientIp(), $this->allowedIps)) {
            return $this->app->handle($request, $type, $catch);
        }

        return new Response('You are not allowed to access this file.', 403);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return self::PRIORITY;
    }
}
