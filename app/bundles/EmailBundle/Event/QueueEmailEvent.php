<?php

namespace Mautic\EmailBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class QueueEmailEvent.
 */
class QueueEmailEvent extends Event
{
    /**
     * @var bool
     */
    private $retry = false;

    public function __construct(private \Swift_Message $message)
    {
    }

    /**
     * @return \Swift_Message
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
