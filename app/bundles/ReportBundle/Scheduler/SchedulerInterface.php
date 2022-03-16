<?php

namespace Mautic\ReportBundle\Scheduler;

interface SchedulerInterface
{
    public function isScheduled();

    public function isScheduledNow(): bool;

    public function isScheduledDaily();

    public function isScheduledWeekly();

    public function isScheduledMonthly();

    public function isScheduledWeekDays();

    public function getScheduleDay();

    public function getScheduleMonthFrequency();
}
