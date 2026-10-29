<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper\EventListener;

use Mautic\EmailBundle\Event\EmailValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EmailValidationSubscriber
{
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailValidationEvent::class)]
    public function onEmailValidation(EmailValidationEvent $event): void
    {
        if ('bad@gmail.com' === $event->getAddress()) {
            $event->setInvalid('bad email');
        } // defaults to valid
    }
}
