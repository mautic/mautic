<?php

namespace Mautic\ReportBundle\Tests\Scheduler\Date;

use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Mautic\ReportBundle\Scheduler\Entity\SchedulerEntity;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\Exception\NoScheduleException;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory;

class DateBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetNextEvent()
    {
        $schedulerTemplateFactory = new SchedulerTemplateFactory();
        $schedulerBuilder         = new SchedulerBuilder($schedulerTemplateFactory);

        $dateBuilder = new DateBuilder($schedulerBuilder);

        $schedulerEntity = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);

        $date = $dateBuilder->getNextEvent($schedulerEntity);

        $expectedDate = (new \DateTime())->setTime(0, 0)->modify('+1 day');
        $this->assertEquals($expectedDate, $date);
    }

    public function testInvalidScheduler()
    {
        $schedulerBuilder = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schedulerEntity = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);

        $schedulerBuilder->expects($this->once())
            ->method('getNextEvent')
            ->with($schedulerEntity)
            ->willThrowException(new InvalidSchedulerException());

        $dateBuilder = new DateBuilder($schedulerBuilder);

        $this->expectException(NoScheduleException::class);

        $dateBuilder->getNextEvent($schedulerEntity);
    }

    public function testSchedulerNotSupported()
    {
        $schedulerBuilder = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schedulerEntity = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);

        $schedulerBuilder->expects($this->once())
            ->method('getNextEvent')
            ->with($schedulerEntity)
            ->willThrowException(new NotSupportedScheduleTypeException());

        $dateBuilder = new DateBuilder($schedulerBuilder);

        $this->expectException(NoScheduleException::class);

        $dateBuilder->getNextEvent($schedulerEntity);
    }

    public function testNoResult()
    {
        $schedulerBuilder = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schedulerEntity = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);

        $schedulerBuilder->expects($this->once())
            ->method('getNextEvent')
            ->with($schedulerEntity)
            ->willReturn([]);

        $dateBuilder = new DateBuilder($schedulerBuilder);

        $this->expectException(NoScheduleException::class);

        $dateBuilder->getNextEvent($schedulerEntity);
    }
}
