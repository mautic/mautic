<?php

namespace Mautic\QueueBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class QueueEvent.
 */
class QueueEvent extends CommonEvent
{
    /**
     * QueueEvent constructor.
     *
     * @param string $protocol
     * @param string $queueName
     */
    public function __construct(private $protocol, private $queueName, private array $payload = [], private ?int $messages = null, private ?int $timeout = null)
    {
    }

    public function getMessages(): ?int
    {
        return $this->messages;
    }

    public function getPayload($returnArray = false): string|array
    {
        return ($returnArray) ? $this->payload : json_encode($this->payload);
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * @param string $protocol
     *
     * @return bool
     */
    public function checkContext($protocol)
    {
        return $protocol == $this->protocol;
    }
}
