<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Class ExceptionController
 */
class ExceptionController extends CommonController
{
    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $env            = $this->factory->getEnvironment();
        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $code           = $exception->getStatusCode();
        $layout         = $env == 'prod' ? 'Error' : 'Exception';

        if ($request->isXmlHttpRequest()) {
            if ($request->query->get('ignoreAjax', false)) {
                return $exception->getMessage();
            } else {
                return new JsonResponse(array(
                    'error' => array(
                        'code'      => $code,
                        'text'      => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                        'exception' => $exception->getMessage(),
                        'trace'     => ($env == 'dev') ? $exception->getTrace() : ''
                    )
                ), $code);
            }
        }

        // If our template doesn't exist, fallback on the generic version
        $templating = $this->get('templating');
        $template   = "MauticCoreBundle:{$layout}:{$code}.html.php";

        if (!$templating->exists($template)) {
            $template = "MauticCoreBundle:{$layout}:generic.html.php";
        }

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'status_code'    => $code,
                'status_text'    => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception'      => $exception,
                'logger'         => $logger,
                'currentContent' => $currentContent,
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
            ),
            'contentTemplate' => $template,
            'passthroughVars' => array(
                'activeLink'     => '#mautic_dashboard_index',
                'mauticContent'  => 'dashboard'
            )
        ));
    }

    /**
     * @param int     $startObLevel
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
