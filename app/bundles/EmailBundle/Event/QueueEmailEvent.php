<?php

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;

/**
 * Class QueueEmailEvent.
 */
class QueueEmailEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    /**
     * @var MauticMessage
     */
    private $message;

    /**
     * @var bool
     */
    private $retry = false;

    public function __construct(MauticMessage $message)
    {
        $this->message = $message;
    }

    public function getMessage(): MauticMessage
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
