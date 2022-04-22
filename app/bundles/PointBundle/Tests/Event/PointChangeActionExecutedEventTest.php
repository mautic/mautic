<?php

namespace Mautic\PointBundle\Tests\Event;

use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Event\PointChangeActionExecutedEvent;

class PointChangeActionExecutedEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Stat|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDetailsMock;

    /**
     * @var Lead|\PHPUnit\Framework\MockObject\MockObject
     */
    private $leadMock;

    /**
     * @var Point|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pointMock;

    /**
     * @var array<int, array<int, array<string, int>>>
     */
    private array $completedActions;

    protected function setUp(): void
    {
        $this->pointMock        = $this->createMock(Point::class);
        $this->leadMock         = $this->createMock(Lead::class);
        $this->eventDetailsMock = $this->createMock(Stat::class);
        $this->completedActions = [
            1 => [
                99 => ['internal_id' => 99],
            ],
            2 => [
                32 => ['internal_id' => 32],
            ],
        ];
    }

    public function testSetStatusFromLogsCannotChangePoints(): void
    {
        $this->pointMock->method('getId')
            ->willReturn(1);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, $this->eventDetailsMock, $this->completedActions);
        $event->setStatusFromLogs();
        $this->assertFalse($event->canChangePoints());
    }

    public function testSetStatusFromLogsCanChangePoints(): void
    {
        $this->pointMock->method('getId')
            ->willReturn(9);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, $this->eventDetailsMock, $this->completedActions);
        $event->setStatusFromLogs();
        $this->assertTrue($event->canChangePoints());
    }

    public function testSetStatusFromLogsByInternalIdCannotChangePoints(): void
    {
        $this->pointMock->method('getId')
            ->willReturn(1);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, $this->eventDetailsMock, $this->completedActions);
        $event->setStatusFromLogsForInternalId(99);
        $this->assertFalse($event->canChangePoints());
    }

    public function testSetStatusFromLogsByInternalIdCanChangePoints(): void
    {
        $this->pointMock->method('getId')
            ->willReturn(1);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, $this->eventDetailsMock, $this->completedActions);
        $event->setStatusFromLogsForInternalId(98);
        $this->assertTrue($event->canChangePoints());
    }
}
