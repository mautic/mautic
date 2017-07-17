<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use LightSaml\Error\LightSamlException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as KernelExceptionListener;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Core\Security;

/**
 * Class ExceptionListener.
 */
class ExceptionListener extends KernelExceptionListener
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * ExceptionListener constructor.
     *
     * @param Router               $router
     * @param LoggerInterface      $controller
     * @param LoggerInterface|null $logger
     */
    public function __construct(Router $router, $controller, LoggerInterface $logger = null)
    {
        parent::__construct($controller, $logger);

        $this->router = $router;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof LightSamlException) {
            // Redirect to login page with message
            $event->getRequest()->getSession()->set(Security::AUTHENTICATION_ERROR, $exception->getMessage());
            $event->setResponse(new RedirectResponse($this->router->generate('login')));

            return;
        }

        // Check for exceptions we don't want to handle
        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException || $exception instanceof LogoutException
        ) {
            return;
        }

        if (!$exception instanceof AccessDeniedHttpException && !$exception instanceof NotFoundHttpException) {
            $this->logException($exception, sprintf('Uncaught PHP Exception %s: "%s" at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()));
        }

        $exception = $event->getException();
        $request   = $event->getRequest();
        $request   = $this->duplicateRequest($exception, $request);
        try {
            $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);

            $event->setResponse($response);
        } catch (\Exception $e) {
            $this->logException(
                $e,
                sprintf(
                    'Exception thrown when handling an exception (%s: %s at %s line %s)',
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );

            $wrapper = $e;

            while ($prev = $wrapper->getPrevious()) {
                if ($exception === $wrapper = $prev) {
                    throw $e;
                }
            }

            $prev = new \ReflectionProperty('Exception', 'previous');
            $prev->setAccessible(true);
            $prev->setValue($wrapper, $exception);

            throw $e;
        }
    }
}
