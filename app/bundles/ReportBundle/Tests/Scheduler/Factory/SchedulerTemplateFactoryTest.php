<?php

namespace Mautic\ReportBundle\Tests\Scheduler\Factory;

use Mautic\ReportBundle\Scheduler\Builder\SchedulerDailyBuilder;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerMonthBuilder;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerNowBuilder;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerWeeklyBuilder;
use Mautic\ReportBundle\Scheduler\Entity\SchedulerEntity;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory;

class SchedulerTemplateFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testNowBuilder(): void
    {
        $schedulerEntity          = new SchedulerEntity(true, SchedulerEnum::UNIT_NOW, null, null);
        $schedulerTemplateFactory = new SchedulerTemplateFactory();
        $builder                  = $schedulerTemplateFactory->getBuilder($schedulerEntity);

        $this->assertInstanceOf(SchedulerNowBuilder::class, $builder);
    }

    public function testDailyBuilder(): void
    {
        $schedulerEntity          = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);
        $schedulerTemplateFactory = new SchedulerTemplateFactory();
        $builder                  = $schedulerTemplateFactory->getBuilder($schedulerEntity);

        $this->assertInstanceOf(SchedulerDailyBuilder::class, $builder);
    }

    public function testWeeklyBuilder(): void
    {
        $schedulerEntity          = new SchedulerEntity(true, SchedulerEnum::UNIT_WEEKLY, null, null);
        $schedulerTemplateFactory = new SchedulerTemplateFactory();
        $builder                  = $schedulerTemplateFactory->getBuilder($schedulerEntity);

        $this->assertInstanceOf(SchedulerWeeklyBuilder::class, $builder);
    }

    public function testMonthlyBuilder(): void
    {
        $schedulerEntity          = new SchedulerEntity(true, SchedulerEnum::UNIT_MONTHLY, null, null);
        $schedulerTemplateFactory = new SchedulerTemplateFactory();
        $builder                  = $schedulerTemplateFactory->getBuilder($schedulerEntity);

        $this->assertInstanceOf(SchedulerMonthBuilder::class, $builder);
    }

    public function testNotSupportedBuilder(): void
    {
        $schedulerEntity          = new SchedulerEntity(true, 'xx', null, null);
        $schedulerTemplateFactory = new SchedulerTemplateFactory();

        $this->expectException(NotSupportedScheduleTypeException::class);
        $schedulerTemplateFactory->getBuilder($schedulerEntity);
    }
}
