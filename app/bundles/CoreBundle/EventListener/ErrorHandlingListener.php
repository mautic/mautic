<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorHandlingListener implements EventSubscriberInterface
{
    private $prevErrorHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 2047],
        ];
    }

    /**
     * @param                 $environment
     * @param LoggerInterface $logger
     */
    public function __construct($environment, LoggerInterface $logger)
    {
        if ($environment == 'prod') {
            $this->logger = $logger;

            // Log PHP fatal errors
            register_shutdown_function([$this, 'handleFatal']);

            // Log general PHP errors
            $this->prevErrorHandler = set_error_handler([$this, 'handleError']);
        }
    }

    /**
     * Log fatal error to Mautic's logs and throw exception for the parent generic error page to catch.
     *
     * @throws \Exception
     */
    public function handleFatal()
    {
        $error = error_get_last();

        if ($error !== null) {
            $name = $this->getErrorName($error['type']);
            $this->logger->error("$name: {$error['message']} - in file {$error['file']} - at line {$error['line']}");

            if ($error['type'] === E_ERROR || $error['type'] === E_CORE_ERROR || $error['type'] === E_USER_ERROR) {
                defined('MAUTIC_OFFLINE') or define('MAUTIC_OFFLINE', 1);

                if (MAUTIC_ENV == 'dev') {
                    $message = "<pre>{$error['message']} - in file {$error['file']} - at line {$error['line']}</pre>";

                    // Get a trace
                    ob_start();
                    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    $trace = ob_get_contents();
                    ob_end_clean();

                    // Remove first item from backtrace as it's this function which
                    // is redundant.
                    $trace = preg_replace('/^#0\s+'.__FUNCTION__."[^\n]*\n/", '', $trace, 1);

                    // Renumber backtrace items.
                    $trace = preg_replace('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

                    $submessage = "<pre>$trace</pre>";
                } else {
                    $message    = 'The site is currently offline due to encountering an error. If the problem persists, please contact the system administrator.';
                    $submessage = 'System administrators, check server logs for errors.';
                }

                include __DIR__.'/../../../../offline.php';
            }
        }
    }

    /**
     * Log PHP information to Mautic's logs.
     *
     * @param        $level
     * @param        $message
     * @param string $file
     * @param int    $line
     * @param array  $context
     *
     * @return mixed
     */
    public function handleError($level, $message, $file = 'unknown', $line = 0, $context = [])
    {
        $errorReporting = error_reporting();
        if ($level & $errorReporting) {
            if ($level & E_NOTICE) {
                $level = LogLevel::NOTICE;
            } elseif ($level & E_WARNING) {
                $level = LogLevel::WARNING;
            } else {
                $level = LogLevel::ERROR;
            }

            $this->logger->log($level, 'PHP '.ucfirst($level).": $message - in file $file - at line $line");

            if ($this->prevErrorHandler) {
                call_user_func($this->prevErrorHandler, $level, $message, $file, $line, $context);
            }
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // Do nothing.  Just want symfony to call the class to set the error handling functions
    }

    /**
     * @param $bit
     *
     * @return string
     */
    private function getErrorName($bit)
    {
        switch ($bit) {
            case E_ERROR:
            case E_USER_ERROR:
            case E_CORE_ERROR:
            case E_RECOVERABLE_ERROR:

                return 'Error';

            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:

                return 'Warning';

            case E_DEPRECATED:
            case E_USER_DEPRECATED:

                return 'Deprecation';

            default:

                return 'Notice';
        }
    }
}
