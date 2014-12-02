<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $layout         = $env == 'prod' ? 'Error' : 'Exception';
        $code           = $exception->getStatusCode();

        if ($forceProd = $request->get('prod')) {
            $layout = 'Error';
        }

        $anonymous     = $this->factory->getSecurity()->isAnonymous();
        if ($anonymous || $forceProd) {
            $baseTemplate  = 'MauticCoreBundle:Default:slim.html.php';
            if ($templatePage = $this->factory->getTheme()->getErrorPageTemplate($code)) {
                $baseTemplate = $templatePage;
            }
        } else{
            $baseTemplate  = 'MauticCoreBundle:Default:content.html.php';
        }

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'baseTemplate'    => $baseTemplate,
                'status_code'     => $code,
                'status_text'     => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception'       => $exception,
                'logger'          => $logger,
                'currentContent'  => $currentContent,
                'isPublicPage'    => $anonymous
            ),
            'contentTemplate' => "MauticCoreBundle:{$layout}:{$code}.html.php",
            'passthroughVars' => array(
                'error' => array(
                    'code'      => $code,
                    'text'      => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                    'exception' => $exception->getMessage(),
                    'trace'     => ($env == 'dev') ? $exception->getTrace() : ''
                )
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
