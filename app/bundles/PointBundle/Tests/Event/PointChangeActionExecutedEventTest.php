<?php

namespace Mautic\PointBundle\Tests\Event;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Event\PointChangeActionExecutedEvent;
use PHPUnit\Framework\MockObject\MockObject;

class PointChangeActionExecutedEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Lead|MockObject
     */
    private $leadMock;

    /**
     * @var Point|MockObject
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

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, new Email(), $this->completedActions);
        $event->setStatusFromLogs();
        $this->assertFalse($event->canChangePoints());
    }

    public function testSetStatusFromLogsCanChangePoints(): void
    {
        $this->pointMock->method('getId')
            ->willReturn(9);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, new Submission(), $this->completedActions);
        $event->setStatusFromLogs();
        $this->assertTrue($event->canChangePoints());
    }

    public function testSetStatusFromLogsByInternalIdCannotChangePoints(): void
    {
        $this->pointMock->method('getId')
            ->willReturn(1);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, new Hit(), $this->completedActions);
        $event->setStatusFromLogsForInternalId(99);
        $this->assertFalse($event->canChangePoints());
    }

    public function testSetStatusFromLogsByInternalIdCanChangePoints(): void
    {
        $this->pointMock->method('getId')
            ->willReturn(1);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, new Asset(), $this->completedActions);
        $event->setStatusFromLogsForInternalId(98);
        $this->assertTrue($event->canChangePoints());
    }
}
