<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\ApiBundle\Helper\RequestHelper;
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
        $class          = $exception->getClass();
        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $layout         = 'prod' == MAUTIC_ENV ? 'Error' : 'Exception';
        $code           = $exception->getStatusCode();

        if (0 === $code) {
            //thrown exception that didn't set a code
            $code = 500;
        }

        // Special handling for oauth and api urls
        if (
            (false !== strpos($request->getUri(), '/oauth') && false === strpos($request->getUri(), 'authorize'))
            || RequestHelper::isApiRequest($request)
            || (!defined('MAUTIC_AJAX_VIEW') && false !== strpos($request->server->get('HTTP_ACCEPT', ''), 'application/json'))
        ) {
            $allowRealMessage =
                'dev' === MAUTIC_ENV ||
                false !== strpos($class, 'UnexpectedValueException') ||
                false !== strpos($class, 'NotFoundHttpException') ||
                false !== strpos($class, 'AccessDeniedHttpException');

            $message   = $allowRealMessage
                ? $exception->getMessage()
                : $this->get('translator')->trans(
                    'mautic.core.error.generic',
                    ['%code%' => $code]
                );
            $dataArray = [
                'errors' => [
                    [
                        'message' => $message,
                        'code'    => $code,
                        'type'    => null,
                    ],
                ],
            ];

            if ('dev' == MAUTIC_ENV) {
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
            if ($templatePage = $this->get('mautic.helper.theme')->getTheme()->getErrorPageTemplate($code)) {
                $baseTemplate = $templatePage;
            }
        }

        $template   = "MauticCoreBundle:{$layout}:{$code}.html.php";
        $templating = $this->get('mautic.helper.templating')->getTemplating();
        if (!$templating->exists($template)) {
            $template = "MauticCoreBundle:{$layout}:base.html.php";
        }

        $statusText = isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '';

        $url      = $request->getRequestUri();
        $urlParts = parse_url($url);

        return $this->delegateView(
            [
                'viewParameters'  => [
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
                        'exception' => ('dev' == MAUTIC_ENV) ? $exception->getMessage() : '',
                        'trace'     => ('dev' == MAUTIC_ENV) ? $exception->getTrace() : '',
                    ],
                    'route' => $urlParts['path'],
                ],
                'responseCode'    => $code,
            ]
        );
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
