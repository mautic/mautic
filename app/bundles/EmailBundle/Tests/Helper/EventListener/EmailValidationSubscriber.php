<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EmailValidationSubscriber.
 */
class EmailValidationSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::ON_EMAIL_VALIDATION => ['onEmailValidation', 0],
        ];
    }

    /**
     * @param EmailValidationEvent $event
     */
    public function onEmailValidation(EmailValidationEvent $event)
    {
        if ('bad@gmail.com' === $event->getAddress()) {
            $event->setInvalid('bad email');
        } // defaults to valid
    }
}
