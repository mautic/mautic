<?php

namespace Mautic\ChannelBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class MessageQueueBatchProcessEvent extends Event
{
    private $messages = [];

    private $channel;

    private $channelId;

    /**
     * MessageQueueBatchProcessEvent constructor.
     *
     * @param $channel
     * @param $channelId
     */
    public function __construct(array $messages, $channel, $channelId)
    {
        $this->messages  = $messages;
        $this->channel   = $channel;
        $this->channelId = $channelId;
    }

    /**
     * @param $channel
     *
     * @return bool
     */
    public function checkContext($channel)
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
