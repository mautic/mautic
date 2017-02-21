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

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorHandlingListener implements EventSubscriberInterface
{
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
     * ErrorHandlingListener constructor.
     *
     * @param LoggerInterface $logger
     * @param LoggerInterface $mainLogger
     * @param LoggerInterface $debugLogger
     */
    public function __construct(LoggerInterface $logger, LoggerInterface $mainLogger, LoggerInterface $debugLogger = null)
    {
        ErrorHandler::getHandler()
            ->setLogger($logger)
            ->setMainLogger($mainLogger)
            ->setDebugLogger($debugLogger);
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // Do nothing.  Just want symfony to call the class to set the error handling functions
    }
}
