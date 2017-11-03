<?php

namespace Scheduler;

use Mautic\ReportBundle\Scheduler\Builder\SchedulerDailyBuilder;
use Mautic\ReportBundle\Scheduler\Entity\SchedulerEntity;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Recurr\Exception\InvalidArgument;
use Recurr\Rule;

class SchedulerDailyBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuilEvent()
    {
        $schedulerDailyBuilder = new SchedulerDailyBuilder();

        $schedulerEntity = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);

        $startDate = (new \DateTime())->setTime(0, 0)->modify('+1 day');
        $rule      = new Rule();
        $rule->setStartDate($startDate)
            ->setCount(1);

        $schedulerDailyBuilder->build($rule, $schedulerEntity);

        $this->assertEquals(Rule::$freqs['DAILY'], $rule->getFreq());
    }

    public function testBuilEventFails()
    {
        $schedulerDailyBuilder = new SchedulerDailyBuilder();

        $schedulerEntity = new SchedulerEntity(true, SchedulerEnum::UNIT_DAILY, null, null);

        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rule->expects($this->once())
            ->method('setFreq')
            ->with('DAILY')
            ->willThrowException(new InvalidArgument());

        $this->expectException(InvalidSchedulerException::class);

        $schedulerDailyBuilder->build($rule, $schedulerEntity);
    }
}
