<?php

namespace Mautic\CoreBundle\EventListener;

use LightSaml\Error\LightSamlException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LazyResponseException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ExceptionListener extends ErrorListener
{
    /**
     * @param LoggerInterface $controller
     */
    public function __construct(
        protected Router $router,
        $controller,
        LoggerInterface $logger = null,
    ) {
        parent::__construct($controller, $logger);
    }

    public function onKernelException(ExceptionEvent $event, string $eventName = null, EventDispatcherInterface $eventDispatcher = null): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof LightSamlException) {
            // Convert the LightSamlException to a AuthenticationException so it can be passed in the session.
            $exception = new AuthenticationException($exception->getMessage());
            // Redirect to login page with message
            $event->getRequest()->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
            $event->setResponse(new RedirectResponse($this->router->generate('login')));

            return;
        }

        // The authentication wraps a response in the LazyResponseException @see \Symfony\Component\Security\Http\Event\LazyResponseEvent::setResponse
        if ($exception instanceof LazyResponseException) {
            $response = $exception->getResponse();

            if ($response instanceof RedirectResponse) {
                return;
            }
        }

        // Check for exceptions we don't want to handle
        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException || $exception instanceof LogoutException
        ) {
            return;
        }

        if (!$exception instanceof AccessDeniedHttpException && !$exception instanceof NotFoundHttpException) {
            $this->logException($exception, sprintf('Uncaught PHP Exception %s: "%s" at %s line %s', $exception::class, $exception->getMessage(), $exception->getFile(), $exception->getLine()));
        }

        $exception = $event->getThrowable();
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
                    $e::class,
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
