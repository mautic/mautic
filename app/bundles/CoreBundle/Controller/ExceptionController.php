<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Class ExceptionController.
 */
class ExceptionController extends CommonController
{
    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $class = $exception->getClass();

        //ignore authentication exceptions
        if (strpos($class, 'Authentication') === false) {
            $env            = $this->factory->getEnvironment();
            $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
            $layout         = $env == 'prod' ? 'Error' : 'Exception';
            $code           = $exception->getStatusCode();
            if ($code === 0) {
                //thrown exception that didn't set a code
                $code = 500;
            }

            // Special handling for oauth and api urls
            if (
                (strpos($request->getUri(), '/oauth') !== false && strpos($request->getUri(), 'authorize') === false) ||
                strpos($request->getUri(), '/api') !== false ||
                (!defined('MAUTIC_AJAX_VIEW') && strpos($request->server->get('HTTP_ACCEPT', ''), 'application/json') !== false)
            ) {
                $message   = ('dev' === MAUTIC_ENV) ? $exception->getMessage() : $this->get('translator')->trans('mautic.core.error.generic', ['%code%' => $code]);
                $dataArray = [
                    'errors' => [
                        [
                            'message' => $message,
                            'code'    => $code,
                            'type'    => null,
                        ],
                    ],
                    // @deprecated 2.6.0 to be removed in 3.0
                    'error' => [
                        'message' => $message.' (`error` is deprecated as of 2.6.0 and will be removed in 3.0. Use the `errors` array instead.)',
                        'code'    => $code,
                    ],
                ];
                if ($env == 'dev') {
                    $dataArray['trace'] = $exception->getTrace();
                }

                // Normal behavior in Symfony dev mode is to send 200 with error message,
                // but this is used in prod mode for all "/api" requests too. (#224)
                return new JsonResponse($dataArray, $code);
            }

            if ($request->get('prod')) {
                $layout = 'Error';
            }

            $anonymous    = $this->get('mautic.security')->isAnonymous();
            $baseTemplate = 'MauticCoreBundle:Default:slim.html.php';
            if ($anonymous) {
                if ($templatePage = $this->factory->getTheme()->getErrorPageTemplate($code)) {
                    $baseTemplate = $templatePage;
                }
            }

            $template   = "MauticCoreBundle:{$layout}:{$code}.html.php";
            $templating = $this->factory->getTemplating();
            if (!$templating->exists($template)) {
                $template = "MauticCoreBundle:{$layout}:base.html.php";
            }

            $statusText = isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '';

            $url      = $request->getRequestUri();
            $urlParts = parse_url($url);

            return $this->delegateView(
                [
                    'viewParameters' => [
                        'baseTemplate'   => $baseTemplate,
                        'status_code'    => $code,
                        'status_text'    => $statusText,
                        'exception'      => $exception,
                        'logger'         => $logger,
                        'currentContent' => $currentContent,
                        'isPublicPage'   => $anonymous,
                    ],
                    'contentTemplate' => $template,
                    'passthroughVars' => [
                        'error' => [
                            'code'      => $code,
                            'text'      => $statusText,
                            'exception' => ($env == 'dev') ? $exception->getMessage() : '',
                            'trace'     => ($env == 'dev') ? $exception->getTrace() : '',
                        ],
                        'route' => $urlParts['path'],
                    ],
                    'responseCode' => $code,
                ]
            );
        }
    }

    /**
     * @param int $startObLevel
     *
     * @return string
     */
    protected function getAndCleanOutputBuffering($startObLevel)
    {
        if (ob_get_level() <= $startObLevel) {
            return '';
        }

        Response::closeOutputBuffers($startObLevel + 1, true);

        return ob_get_clean();
    }
}
