<?php

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Symfony\Contracts\EventDispatcher\Event;

class QueueEmailEvent extends Event
{
    private bool $retry = false;

    public function __construct(
        private readonly MauticMessage $message,
    ) {
    }

    public function getMessage(): MauticMessage
    {
        return $this->message;
    }

    /**
     * Sets whether the sending of the message should be tried again.
     */
    public function tryAgain(): void
    {
        $this->retry = true;
    }

    public function shouldTryAgain(): bool
    {
        return $this->retry;
    }
}
