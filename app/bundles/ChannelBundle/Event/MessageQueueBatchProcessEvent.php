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

use Symfony\Component\EventDispatcher\Event;

class MessageQueueBatchProcessEvent extends Event
{
    private $messages = [];

    private $channel;

    private $channelId;

    /**
     * MessageQueueBatchProcessEvent constructor.
     *
     * @param array $messages
     * @param       $channel
     * @param       $channelId
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
