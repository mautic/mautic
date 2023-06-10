<?php

namespace Mautic\QueueBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;

/**
 * Class QueueConsumerEvent.
 */
class QueueConsumerEvent extends CommonEvent
{
    /**
     * @var string
     */
    private $result;

    /**
     * @param mixed[] $payload
     */
    public function __construct(private $payload = [])
    {
        $this->result  = QueueConsumerResults::DO_NOT_ACKNOWLEDGE;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param string $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Checks if the event is for specific transport.
     *
     * @param string $transport
     *
     * @return bool
     */
    public function checkTransport($transport)
    {
        return isset($this->payload['transport']) && $this->payload['transport'] === $transport;
    }
}
