<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Event;

use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Event\CommonEvent;

class MessageEvent extends CommonEvent
{
    /**
     * MessageEvent constructor.
     *
     * @param Message $message
     * @param bool    $isNew
     */
    public function __construct(Message $message, $isNew = false)
    {
        $this->entity = $message;
        $this->isNew  = $isNew;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->entity;
    }

    /**
     * Sets the Sms entity.
     *
     * @param Message $message
     */
    public function setSms(Message $message)
    {
        $this->entity = $message;
    }
}
