<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class QueueEmailEvent.
 */
class QueueEmailEvent extends Event
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
