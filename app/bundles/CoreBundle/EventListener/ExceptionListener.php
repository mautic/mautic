<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use LightSaml\Error\LightSamlException;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Helper\Dsn\Dsn;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ExceptionListener extends ErrorListener
{
    public function __construct(private Router $router, private Configurator $configurator, string $controller, LoggerInterface $logger = null)
    {
        parent::__construct($controller, $logger);

        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event, string $eventName = null, EventDispatcherInterface $eventDispatcher = null): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof LightSamlException) {
            // Redirect to login page with message
            $event->getRequest()->getSession()->set(Security::AUTHENTICATION_ERROR, $exception->getMessage());
            $event->setResponse(new RedirectResponse($this->router->generate('login')));

            return;
        }

        // Check for exceptions we don't want to handle
        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException || $exception instanceof LogoutException) {
            return;
        }

        if ($exception instanceof UnsupportedSchemeException) {
            $this->handleUnsupportedMailerSchemaException($event);
        }

        if (!$exception instanceof AccessDeniedHttpException && !$exception instanceof NotFoundHttpException) {
            $this->logException($exception, sprintf('Uncaught PHP Exception %s: "%s" at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()));
        }

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

    /**
     * If an unsupported transport is uninstalled for example, Mautic will be down and users cannot fix the DSN via the UI.
     * This method will reset the DSN to a fallback value allow users to re-confgure DSN via the UI on the second refresh.
     */
    private function handleUnsupportedMailerSchemaException(ExceptionEvent $event): void
    {
        $option   = 'mailer_dsn';
        $dns      = Dsn::fromString($this->configurator->getParameters()[$option] ?? 'unknown://@unknown');
        $fallback = 'smtp://localhost:25';
        $message  = "The {$option} scheme {$dns->getScheme()} is unsupported by any installed transport. The {$option} option has been reset to {$fallback} so you can at least load the application. Please check your mailer configuration.";
        $this->logException($event->getThrowable(), $message);
        $this->configurator->mergeParameters([$option => $fallback]);
        $this->configurator->write();

        throw new \Exception("{$message} Refresh the page to configure the mailer.");
    }
}
