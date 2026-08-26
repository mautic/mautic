<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Event;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Event\CommonEvent;

final class MessageQueueEvent extends CommonEvent
{
    public function __construct(MessageQueue $entity, bool $isNew = false)
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
    public function setMessageQueue($entity): void
    {
        $this->entity = $entity;
    }
}
