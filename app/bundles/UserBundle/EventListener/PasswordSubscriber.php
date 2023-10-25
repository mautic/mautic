<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PasswordSubscriber implements EventSubscriberInterface
{
    public function __construct(private PasswordStrengthEstimatorModel $passwordStrengthEstimatorModel)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_FORM_POST_LOCAL_PASSWORD_AUTHENTICATION => ['onUserFormAuthentication', 0],
        ];
    }

    public function onUserFormAuthentication(AuthenticationEvent $authenticationEvent): void
    {
        $userPassword = $authenticationEvent->getToken()->getCredentials(); /* @phpstan-ignore-line getCredentials() is deprecated since Symfony 5.4, refactoring needed */

        if (!$this->passwordStrengthEstimatorModel->validate($userPassword)) {
            throw new WeakPasswordException();
        }
    }
}
