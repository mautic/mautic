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

class CORSMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    use ConfigAwareTrait;

    const PRIORITY = 1000;

    /**
     * @var array
     */
    protected $corsHeaders = [
        'Access-Control-Allow-Origin'      => '*',
        'Access-Control-Allow-Headers'     => 'Origin, X-Requested-With, Content-Type',
        'Access-Control-Allow-Methods'     => 'PUT, GET, POST, DELETE, OPTIONS',
        'Access-Control-Allow-Credentials' => 'true',
    ];

    /**
     * @var bool
     */
    protected $requestOriginIsValid = false;

    /**
     * @var bool
     */
    protected $restrictCORSDomains = true;

    /**
     * @var array
     */
    protected $validCORSDomains = [];

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
        $this->app                 = $app;
        $this->config              = $this->getConfig();
        $this->restrictCORSDomains = array_key_exists('cors_restrict_domains', $this->config) ? (bool) $this->config['cors_restrict_domains'] : true;
        $this->validCORSDomains    = array_key_exists('cors_valid_domains', $this->config) ? (array) $this->config['cors_valid_domains'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $this->corsHeaders['Access-Control-Allow-Origin'] = $this->getAllowOriginHeaderValue($request);

        // Capture all OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', Response::HTTP_NO_CONTENT);

            // If this is a valid OPTIONS request, set the CORS headers on the Response and exit.
            if (
                $this->requestOriginIsValid
                && $request->headers->has('Access-Control-Request-Headers')
                && $request->headers->has('Origin')
            ) {
                foreach ($this->corsHeaders as $header => $value) {
                    $response->headers->set($header, $value);
                }
            }

            return $response;
        }

        $response = $this->app->handle($request, $type, $catch);

        // Add standard CORS headers to any XHR
        if ($request->isXmlHttpRequest()) {
            foreach ($this->corsHeaders as $header => $value) {
                $response->headers->set($header, $value);
            }
        }

        return $response;
    }

    /**
     * Get the value for the Access-Control-Allow-Origin header
     * based on the Request and local configuration options.
     *
     * @param Request $request
     *
     * @return string|null
     */
    private function getAllowOriginHeaderValue(Request $request)
    {
        $origin = $request->headers->get('Origin');

        // If we're not restricting domains, set the header to the request origin
        if (!$this->restrictCORSDomains || in_array($origin, $this->validCORSDomains)) {
            $this->requestOriginIsValid = true;

            return $origin;
        }

        $this->requestOriginIsValid = false;

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return self::PRIORITY;
    }
}
