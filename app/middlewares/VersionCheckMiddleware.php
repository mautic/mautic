<?php

namespace Mautic\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class VersionCheckMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    public const PRIORITY = 10;

    /**
     * @var HttpKernelInterface
     */
    protected $app;

    /**
     * @var string
     */
    private $minimumPHPVersion;

    /**
     * @var string
     */
    private $maximumPHPVersion;

    public function __construct(HttpKernelInterface $app)
    {
        $this->app = $app;

        $metadata = json_decode(
            file_get_contents(__DIR__.'/../release_metadata.json'),
            true
        );

        $this->minimumPHPVersion = $metadata['minimum_php_version'];
        $this->maximumPHPVersion = $metadata['maximum_php_version'];
    }

    /**
     * Check Minimum / Maximum PHP versions.
     *
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        // Are we running the minimum version?
        if (version_compare(PHP_VERSION, $this->minimumPHPVersion, 'lt')) {
            return new Response('Your server does not meet the minimum PHP requirements. Mautic requires PHP version '.$this->minimumPHPVersion.' while your server has '.PHP_VERSION.'. Please contact your host to update your PHP installation.', 500);
        }

        // Are we running a version newer than what Mautic supports?
        if (version_compare(PHP_VERSION, $this->maximumPHPVersion, 'gt')) {
            return new Response('Mautic does not support PHP version '.PHP_VERSION.' at this time. To use Mautic, you will need to downgrade to an earlier version.', 500);
        }

        return $this->app->handle($request, $type, $catch);
    }

    public function getPriority()
    {
        return self::PRIORITY;
    }
}
