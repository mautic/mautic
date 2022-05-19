<?php

namespace Mautic\QueueBundle\Tests\Event;

use Mautic\QueueBundle\Event\QueueConsumerEvent;

class QueueConsumerEventTest extends \PHPUnit\Framework\TestCase
{
    public function testCheckTransportIfNoTransport()
    {
        $queueConsumerEvent = new QueueConsumerEvent();
        $this->assertEquals(false, $queueConsumerEvent->checkTransport('transportName'));
    }

    public function testCheckTransportIfWrongTransport()
    {
        $queueConsumerEvent = new QueueConsumerEvent(['transport' => 'wrongTransportName']);
        $this->assertEquals(false, $queueConsumerEvent->checkTransport('transportName'));
    }

    public function testCheckTransportIfCorrectTransport()
    {
        $queueConsumerEvent = new QueueConsumerEvent(['transport' => 'transportName']);
        $this->assertEquals(true, $queueConsumerEvent->checkTransport('transportName'));
    }
}
