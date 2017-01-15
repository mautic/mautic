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

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;

/**
 * Class ConsoleExceptionListener.
 */
class ConsoleExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $command   = $event->getCommand();
        $exception = $event->getException();

        // Log error with trace
        $trace = (MAUTIC_ENV == 'dev') ? "\n[stack trace]\n".$exception->getTraceAsString() : '';

        $message = sprintf(
            '%s: %s (uncaught exception) at %s line %s while running console command `%s`%s',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $command->getName(),
            $trace
        );

        // Use notice so it makes it to the log all "perttified" (using error spits it out to console and not the log)
        $this->logger->notice($message);
    }
}
