<?php

namespace Mautic\ReportBundle\Scheduler\Factory;

use Mautic\ReportBundle\Scheduler\Builder\SchedulerDailyBuilder;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerMonthBuilder;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerNowBuilder;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerWeeklyBuilder;
use Mautic\ReportBundle\Scheduler\BuilderInterface;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;

class SchedulerTemplateFactory
{
    /**
     * @return BuilderInterface
     *
     * @throws NotSupportedScheduleTypeException
     */
    public function getBuilder(SchedulerInterface $scheduler)
    {
        if ($scheduler->isScheduledNow()) {
            return new SchedulerNowBuilder();
        }
        if ($scheduler->isScheduledDaily()) {
            return new SchedulerDailyBuilder();
        }
        if ($scheduler->isScheduledWeekly()) {
            return new SchedulerWeeklyBuilder();
        }
        if ($scheduler->isScheduledMonthly()) {
            return new SchedulerMonthBuilder();
        }

        throw new NotSupportedScheduleTypeException();
    }
}
