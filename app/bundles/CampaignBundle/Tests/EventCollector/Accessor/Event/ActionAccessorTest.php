<?php

namespace Mautic\CampaignBundle\Tests\EventCollector\Accessor\Event;

use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;

class ActionAccessorTest extends \PHPUnit\Framework\TestCase
{
    public function testBatchEventNameIsNotExtra()
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test']);

        $this->assertEmpty($actionAccessor->getExtraProperties());
    }

    public function testBatchNameIsReturned()
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test']);

        $this->assertEquals('test', $actionAccessor->getBatchEventName());
    }

    public function testExtraParamIsReturned()
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test', 'foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $actionAccessor->getExtraProperties());
    }
}
