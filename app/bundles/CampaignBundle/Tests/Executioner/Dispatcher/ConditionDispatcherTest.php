<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\Dispatcher;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ConditionEvent;
use Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AllowMockObjectsWithoutExpectations]
final class ConditionDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testConditionEventIsDispatched(): void
    {
        $matcher = $this->exactly(2);

        $this->dispatcher->expects($matcher)
            ->method('dispatch')->willReturnCallback(function (object $event, ?string $eventName = null) use ($matcher): object {
                $this->assertInstanceOf(ConditionEvent::class, $event);
                if (1 === $matcher->numberOfInvocations()) {
                    // dispatched by event class alone
                    $this->assertNull($eventName);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(CampaignEvents::ON_EVENT_CONDITION_EVALUATION, $eventName);
                }

                return $event;
            });

        new ConditionDispatcher($this->dispatcher)->dispatchEvent($this->createStub(\Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor::class), new LeadEventLog());
    }
}
