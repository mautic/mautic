<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventCollector\Accessor\Event;

use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use PHPUnit\Framework\TestCase;

final class ActionAccessorTest extends TestCase
{
    public function testBatchEventNameIsNotExtra(): void
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test']);

        $this->assertEmpty($actionAccessor->getExtraProperties());
    }

    public function testBatchNameIsReturned(): void
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test']);

        $this->assertEquals('test', $actionAccessor->getBatchEventName());
    }

    public function testExtraParamIsReturned(): void
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test', 'foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $actionAccessor->getExtraProperties());
    }
}
