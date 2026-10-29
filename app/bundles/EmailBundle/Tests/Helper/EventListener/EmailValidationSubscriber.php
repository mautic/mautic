<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\EmailBundle\Event\EmailValidationEvent;

final class EmailValidationSubscriber
{
    #[AsEventListener(event: EmailValidationEvent::class)]
    public function onEmailValidation(EmailValidationEvent $event): void
    {
        if ('bad@gmail.com' === $event->getAddress()) {
            $event->setInvalid('bad email');
        } // defaults to valid
    }
}
