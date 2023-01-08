<?php

namespace Mautic\ChannelBundle\Event;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Event\CommonEvent;

class MessageQueueEvent extends CommonEvent
{
    /**
     * MessageQueueEvent constructor.
     *
     * @param bool $isNew
     */
    public function __construct(MessageQueue $entity, $isNew = false)
    {
        $this->entity = $entity;
        $this->isNew  = $isNew;
    }

    /**
     * @return MessageQueue
     */
    public function getMessageQueue()
    {
        return $this->entity;
    }

    /**
     * @param MessageQueue $entity
     */
    public function setMessageQueue($entity)
    {
        $this->entity = $entity;
    }
}
