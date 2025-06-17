<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\Dispatcher;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ConditionEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConditionDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject|ConditionAccessor
     */
    private MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->config     = $this->createMock(ConditionAccessor::class);
    }

    public function testConditionEventIsDispatched(): void
    {
        $this->config->expects($this->once())
            ->method('getEventName')
            ->willReturn('something');
        $matcher = $this->exactly(2);

        $this->dispatcher->expects($matcher)
            ->method('dispatch')->willReturnCallback(function (object $event, string $eventName) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertInstanceOf(ConditionEvent::class, $event);
                    $this->assertSame('something', $eventName);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertInstanceOf(ConditionEvent::class, $event);
                    $this->assertSame(CampaignEvents::ON_EVENT_CONDITION_EVALUATION, $eventName);
                }

                return $event;
            });

        (new ConditionDispatcher($this->dispatcher))->dispatchEvent($this->config, new LeadEventLog());
    }
}
