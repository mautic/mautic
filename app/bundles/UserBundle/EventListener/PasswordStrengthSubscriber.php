<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\UserBundle\Security\Authenticator\Passport\Badge\PasswordStrengthBadge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * This must record a PW before CheckCredentialsListener will take an effect.
 * Subscriber will add a password check badge.
 */
final class PasswordStrengthSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['checkPassport', 100],
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(PasswordCredentials::class)) {
            return;
        }

        $badge = $passport->getBadge(PasswordCredentials::class);
        \assert($badge instanceof PasswordCredentials);
        $presentedPassword = $badge->getPassword();

        $passport->addBadge(new PasswordStrengthBadge($presentedPassword));
    }
}
