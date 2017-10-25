<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler;

interface SchedulerInterface
{
    public function isScheduled();

    public function isScheduledDaily();

    public function isScheduledWeekly();

    public function isScheduledMonthly();

    public function getScheduleDay();

    public function getScheduleMonthFrequency();
}
