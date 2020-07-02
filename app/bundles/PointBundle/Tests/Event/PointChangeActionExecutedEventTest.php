<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Tests\Event;

use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Event\PointChangeActionExecutedEvent;

class PointChangeActionExecutedEventTest extends \PHPUnit\Framework\TestCase
{
    private $eventDetailsMock;

    private $leadMock;

    private $pointMock;

    private $completedActions;

    protected function setUp()
    {
        $this->pointMock        = $this->createMock(Point::class);
        $this->leadMock         = $this->createMock(Lead::class);
        $this->eventDetailsMock = $this->createMock(Stat::class);
        $this->completedActions = [
            1=> [
                'internal_id'=> 99,
            ],
            2=> [
                'internal_id'=> 32,
            ],
        ];
    }

    public function testSetStatusFromLogsCannotCahngePoints()
    {
        $this->pointMock->method('getId')
            ->willReturn(1);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, $this->eventDetailsMock, $this->completedActions);
        $event->setStatusFromLogs();
        $this->assertFalse($event->canChangePoints());
    }

    public function testSetStatusFromLogsCanChangePoints()
    {
        $this->pointMock->method('getId')
            ->willReturn(9);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, $this->eventDetailsMock, $this->completedActions);
        $event->setStatusFromLogs();
        $this->assertTrue($event->canChangePoints());
    }

    public function testSetStatusFromLogsByInternalIdCannotChangePoints()
    {
        $this->pointMock->method('getId')
            ->willReturn(1);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, $this->eventDetailsMock, $this->completedActions);
        $event->setStatusFromLogsForInternalId(99);
        $this->assertFalse($event->canChangePoints());
    }

    public function testSetStatusFromLogsByInternalIdCanChangePoints()
    {
        $this->pointMock->method('getId')
            ->willReturn(1);

        $event = new PointChangeActionExecutedEvent($this->pointMock, $this->leadMock, $this->eventDetailsMock, $this->completedActions);
        $event->setStatusFromLogsForInternalId(98);
        $this->assertTrue($event->canChangePoints());
    }
}
