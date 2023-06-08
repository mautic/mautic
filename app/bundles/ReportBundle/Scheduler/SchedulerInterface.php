<?php

namespace Mautic\ReportBundle\Scheduler;

interface SchedulerInterface
{
    public function isScheduled(): bool;

    public function isScheduledNow(): bool;

    public function isScheduledDaily(): bool;

    public function isScheduledWeekly(): bool;

    public function isScheduledMonthly(): bool;

    public function isScheduledWeekDays(): bool;

    public function getScheduleDay(): ?string;

    public function getScheduleMonthFrequency(): ?string;

    public function getScheduleTimezone(): ?string;

    public function getScheduleTime(): ?string;
}
