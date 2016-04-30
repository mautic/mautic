<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 2047)
        );
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
            register_shutdown_function(array($this, 'handleFatal'));

            // Log general PHP errors
            $this->prevErrorHandler = set_error_handler(array($this, 'handleError'));
        }
    }

    /**
     * Log fatal error to Mautic's logs and throw exception for the parent generic error page to catch
     *
     * @throws \Exception
     */
    public function handleFatal()
    {
        $error = error_get_last();

        if ($error !== null) {
            $this->logger->error("Fatal: {$error['message']} - in file {$error['file']} - at line {$error['line']}");

            defined('MAUTIC_OFFLINE') or define('MAUTIC_OFFLINE', 1);
            $message    = 'The site is currently offline due to encountering an error. If the problem persists, please contact the system administrator.';
            $submessage = 'System administrators, check server logs for errors.';
            include __DIR__ . '/../../../../offline.php';
        }
    }

    /**
     * Log PHP information to Mautic's logs
     *
     * @param        $level
     * @param        $message
     * @param string $file
     * @param int    $line
     * @param array  $context
     *
     * @return mixed
     */
    public function handleError($level, $message, $file = 'unknown', $line = 0, $context = array())
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

            $this->logger->log($level, "PHP " . ucfirst($level) . ": $message - in file $file - at line $line");

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
}