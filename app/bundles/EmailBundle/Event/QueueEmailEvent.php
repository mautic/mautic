<?php

namespace Mautic\EmailBundle\Event;

/**
 * Class QueueEmailEvent.
 */
class QueueEmailEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    /**
     * @var \Swift_Message
     */
    private $message;

    /**
     * @var bool
     */
    private $retry = false;

    public function __construct(\Swift_Message $message)
    {
        $this->message = $message;
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
