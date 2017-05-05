<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Event;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Event\CommonEvent;

class MessageQueueEvent extends CommonEvent
{
    /**
     * MessageQueueEvent constructor.
     *
     * @param MessageQueue $entity
     * @param bool         $isNew
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
