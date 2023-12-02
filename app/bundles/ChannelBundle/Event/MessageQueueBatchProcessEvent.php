<?php

namespace Mautic\ChannelBundle\Event;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Symfony\Contracts\EventDispatcher\Event;

class MessageQueueBatchProcessEvent extends Event
{
    /**
     * @var MessageQueue[]
     */
    private array $messages;

    private $channel;

    private $channelId;

    /**
     * @param MessageQueue[] $messages
     */
    public function __construct(array $messages, $channel, $channelId)
    {
        $this->messages  = $messages;
        $this->channel   = $channel;
        $this->channelId = $channelId;
    }

    public function checkContext($channel): bool
    {
        return $channel === $this->channel;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return mixed
     */
    public function getChannelId()
    {
        return $this->channelId;
    }
}
