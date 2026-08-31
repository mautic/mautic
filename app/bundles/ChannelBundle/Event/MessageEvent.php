<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Event;

use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Event\CommonEvent;

final class MessageEvent extends CommonEvent
{
    public function __construct(Message $message, bool $isNew = false)
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
}
