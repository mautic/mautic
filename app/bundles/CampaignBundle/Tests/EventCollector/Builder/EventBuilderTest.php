<?php

namespace Mautic\CampaignBundle\Tests\EventCollector\Builder;

use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\Builder\EventBuilder;

class EventBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testActionsAreConvertedToAccessor()
    {
        $array = [
            'some.action'  => [
                'batchEventName' => 'some.action',
            ],
            'other.action' => [
                'batchEventName' => 'other.action',
            ],
        ];

        $converted = EventBuilder::buildActions($array);

        $this->assertCount(2, $converted);
        $this->assertInstanceOf(ActionAccessor::class, $converted['some.action']);
        $this->assertEquals('some.action', $converted['some.action']->getBatchEventName());
        $this->assertInstanceOf(ActionAccessor::class, $converted['other.action']);
        $this->assertEquals('other.action', $converted['other.action']->getBatchEventName());
    }

    public function testConditionsAreConvertedToAccessor()
    {
        $array = [
            'some.condition'  => [
                'eventName' => 'some.condition',
            ],
            'other.condition' => [
                'eventName' => 'other.condition',
            ],
        ];

        $converted = EventBuilder::buildConditions($array);

        $this->assertCount(2, $converted);
        $this->assertInstanceOf(ConditionAccessor::class, $converted['some.condition']);
        $this->assertEquals('some.condition', $converted['some.condition']->getEventName());
        $this->assertInstanceOf(ConditionAccessor::class, $converted['other.condition']);
        $this->assertEquals('other.condition', $converted['other.condition']->getEventName());
    }

    public function testDecisionsAreConvertedToAccessor()
    {
        $array = [
            'some.decision'  => [
                'eventName' => 'some.decision',
            ],
            'other.decision' => [
                'eventName' => 'other.decision',
            ],
        ];

        $converted = EventBuilder::buildDecisions($array);

        $this->assertCount(2, $converted);
        $this->assertInstanceOf(DecisionAccessor::class, $converted['some.decision']);
        $this->assertEquals('some.decision', $converted['some.decision']->getEventName());
        $this->assertInstanceOf(DecisionAccessor::class, $converted['other.decision']);
        $this->assertEquals('other.decision', $converted['other.decision']->getEventName());
    }
}
