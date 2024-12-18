<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Mautic\UserBundle\Security\Authenticator\Passport\Badge\PasswordStrengthBadge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

final class PasswordSubscriber implements EventSubscriberInterface
{
    public function __construct(private PasswordStrengthEstimatorModel $passwordStrengthEstimatorModel)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['checkPassport', -100], // After default password checker
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(PasswordStrengthBadge::class)) {
            return;
        }

        $badge = $passport->getBadge(PasswordStrengthBadge::class);
        \assert($badge instanceof PasswordStrengthBadge);
        $presentedPassword = $badge->getPresentedPassword();
        if ('' === $presentedPassword) {
            throw new BadCredentialsException('The presented password cannot be empty.');
        }

        if (!$this->passwordStrengthEstimatorModel->validate($presentedPassword)) {
            throw new WeakPasswordException();
        }

        $badge->setResolved();
    }
}
