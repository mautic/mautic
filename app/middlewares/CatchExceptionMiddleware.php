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

use Mautic\CoreBundle\Exception\DatabaseConnectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CatchExceptionMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    const PRIORITY = 100;

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
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        try {
            $response = $this->app->handle($request, $type, $catch);

            if ($response instanceof Response) {
                return $response;
            }
        } catch (DatabaseConnectionException $e) {
            $message = $e->getMessage();
        } catch (\Exception $e) {
            error_log($e);

            if (MAUTIC_ENV == 'dev') {
                $message    = "<pre>{$e->getMessage()} - in file {$e->getFile()} - at line {$e->getLine()}</pre>";
                $submessage = "<pre>{$e->getTraceAsString()}</pre>";
            } else {
                $message    = 'The site is currently offline due to encountering an error. If the problem persists, please contact the system administrator.';
                $submessage = 'System administrators, check server logs for errors.';
            }
        }

        if (isset($message)) {
            define('MAUTIC_OFFLINE', 1);

            ob_start();
            include MAUTIC_ROOT_DIR.'/offline.php';
            $content = ob_get_clean();

            return new Response($content, 500);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return self::PRIORITY;
    }
}
