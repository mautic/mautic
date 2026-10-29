<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ErrorHandlingListener
{
    public function __construct(LoggerInterface $logger, LoggerInterface $mainLogger, ?LoggerInterface $debugLogger = null)
    {
        ErrorHandler::getHandler()
            ->setLogger($logger)
            ->setMainLogger($mainLogger)
            ->setDebugLogger($debugLogger);
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: KernelEvents::REQUEST, priority: 2047)]
    public function onKernelRequest(RequestEvent $event): void
    {
        // Do nothing.  Just want symfony to call the class to set the error handling functions
    }
}
