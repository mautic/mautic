<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;

class ConsoleErrorListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $command   = $event->getCommand();
        $exception = $event->getError();

        // Log error with trace
        $message = sprintf(
            '%s: %s (uncaught exception) at %s line %s while running console command `%s`%s',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            empty($command) ? 'UNKNOWN' : $command->getName(),
            "\n[stack trace]\n".$exception->getTraceAsString()
        );

        $this->logger->error($message);
    }
}
