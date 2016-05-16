<?php

namespace Mautic\Middleware;

use Mautic\CoreBundle\Exception\DatabaseConnectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CatchExceptionMiddleware implements HttpKernelInterface
{
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
            define('MAUTIC_OFFLINE', 1);
            $message = $e->getMessage();
        } catch (\Exception $e) {
            error_log($e);
            define('MAUTIC_OFFLINE', 1);
            $message    = 'The site is currently offline due to encountering an error. If the problem persists, please contact the system administrator.';
            $submessage = 'System administrators, check server logs for errors.';
        }

        include MAUTIC_ROOT_DIR . '/offline.php';
        exit;
    }
}
