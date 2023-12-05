<?php

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Symfony\Contracts\EventDispatcher\Event;

class QueueEmailEvent extends Event
{
    private \Mautic\EmailBundle\Mailer\Message\MauticMessage $message;

    /**
     * @var bool
     */
    private $retry = false;

    public function __construct(MauticMessage $message)
    {
        $this->message = $message;
    }

    /**
     * @return MauticMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets whether the sending of the message should be tried again.
     */
    public function tryAgain()
    {
        $this->retry = true;
    }

    /**
     * @return bool
     */
    public function shouldTryAgain()
    {
        return $this->retry;
    }
}
