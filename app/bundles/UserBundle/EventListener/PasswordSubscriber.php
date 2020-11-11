<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\EventListener;

use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PasswordSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_FORM_AUTHENTICATION     => ['onUserFormAuthentication', 0],
        ];
    }

    public function onUserFormAuthentication(AuthenticationEvent $authenticationEvent): void
    {
    }
}
