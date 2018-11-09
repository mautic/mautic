<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class VersionCheckMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    const PRIORITY = 10;

    const MAUTIC_MINIMUM_PHP = '5.6.19';
    const MAUTIC_MAXIMUM_PHP = '7.1.999';

    /**
     * @var HttpKernelInterface
     */
    protected $app;

    /**
     * CatchExceptionMiddleware constructor.
     *
     * @param HttpKernelInterface $app
     */
    public function __construct(HttpKernelInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Check Minimum / Maximum PHP versions.
     *
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        // Are we running the minimum version?
        if (version_compare(PHP_VERSION, self::MAUTIC_MINIMUM_PHP, 'lt')) {
            return new Response('Your server does not meet the minimum PHP requirements. Mautic requires PHP version '.self::MAUTIC_MINIMUM_PHP.' while your server has '.PHP_VERSION.'. Please contact your host to update your PHP installation.', 500);
        }

        // Are we running a version newer than what Mautic supports?
        if (version_compare(PHP_VERSION, self::MAUTIC_MAXIMUM_PHP, 'gt')) {
            return new Response('Mautic does not support PHP version '.PHP_VERSION.' at this time. To use Mautic, you will need to downgrade to an earlier version.', 500);
        }

        return $this->app->handle($request, $type, $catch);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return self::PRIORITY;
    }
}
