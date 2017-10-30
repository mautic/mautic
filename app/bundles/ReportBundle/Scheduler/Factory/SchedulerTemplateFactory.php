<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Factory;

use Mautic\ReportBundle\Scheduler\Builder\SchedulerDailyBuilder;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerMonthBuilder;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerWeeklyBuilder;
use Mautic\ReportBundle\Scheduler\BuilderInterface;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;

class SchedulerTemplateFactory
{
    /**
     * @param SchedulerInterface $scheduler
     *
     * @return BuilderInterface
     *
     * @throws NotSupportedScheduleTypeException
     */
    public function getBuilder(SchedulerInterface $scheduler)
    {
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
