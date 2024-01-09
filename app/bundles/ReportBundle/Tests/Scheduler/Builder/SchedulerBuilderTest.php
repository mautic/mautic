<?php

namespace Mautic\ReportBundle\Tests\Scheduler\Builder;

use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Mautic\ReportBundle\Scheduler\Entity\SchedulerEntity;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory;
use Recurr\Recurrence;

class SchedulerBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetNextEvent(): void
    {
        $schedulerTemplateFactory = new SchedulerTemplateFactory();
        $schedulerBuilder         = new SchedulerBuilder($schedulerTemplateFactory);

        $schedulerEntity = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);

        $events = $schedulerBuilder->getNextEvent($schedulerEntity);

        $event = $events[0];
        $this->assertInstanceOf(Recurrence::class, $event);

        $expectedDate = (new \DateTime())->setTime(0, 0)->modify('+1 day');
        $this->assertEquals($expectedDate, $event->getStart());
    }

    public function testGetNextEvents(): void
    {
        $schedulerTemplateFactory = new SchedulerTemplateFactory();
        $schedulerBuilder         = new SchedulerBuilder($schedulerTemplateFactory);

        $schedulerEntity = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);

        $events = $schedulerBuilder->getNextEvents($schedulerEntity, 3);

        $event = $events[0];
        $this->assertInstanceOf(Recurrence::class, $event);

        $expectedDate = (new \DateTime())->setTime(0, 0)->modify('+1 day');
        $this->assertEquals($expectedDate, $event->getStart());

        $event = $events[1];
        $expectedDate->modify('+1 day');
        $this->assertEquals($expectedDate, $event->getStart());

        $event = $events[2];
        $expectedDate->modify('+1 day');
        $this->assertEquals($expectedDate, $event->getStart());
    }

    public function testNoScheduler(): void
    {
        $schedulerTemplateFactory = new SchedulerTemplateFactory();
        $schedulerBuilder         = new SchedulerBuilder($schedulerTemplateFactory);

        $SchedulerEntity = new SchedulerEntity(false, SchedulerEnum::UNIT_DAILY, null, null);

        $this->expectException(InvalidSchedulerException::class);

        $schedulerBuilder->getNextEvent($SchedulerEntity);
    }
}
