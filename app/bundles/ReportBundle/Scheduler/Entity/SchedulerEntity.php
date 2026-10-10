<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Scheduler\Entity;

use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;

final class SchedulerEntity implements SchedulerInterface
{
    /**
     * @param bool        $isScheduled
     * @param string|null $scheduleUnit
     * @param string|null $scheduleDay
     * @param string|null $scheduleMonthFrequency
     */
    public function __construct(
        private $isScheduled,
        private $scheduleUnit,
        private $scheduleDay,
        private $scheduleMonthFrequency,
    ) {
    }

    /**
     * @return bool
     */
    public function isScheduled()
    {
        return $this->isScheduled;
    }

    /**
     * @return string|null
     */
    public function getScheduleUnit()
    {
        return $this->scheduleUnit;
    }

    /**
     * @return string|null
     */
    public function getScheduleDay()
    {
        return $this->scheduleDay;
    }

    /**
     * @return string|null
     */
    public function getScheduleMonthFrequency()
    {
        return $this->scheduleMonthFrequency;
    }

    public function isScheduledNow(): bool
    {
        return SchedulerEnum::UNIT_NOW === $this->scheduleUnit;
    }

    public function isScheduledDaily(): bool
    {
        return SchedulerEnum::UNIT_DAILY === $this->scheduleUnit;
    }

    public function isScheduledWeekly(): bool
    {
        return SchedulerEnum::UNIT_WEEKLY === $this->scheduleUnit;
    }

    public function isScheduledMonthly(): bool
    {
        return SchedulerEnum::UNIT_MONTHLY === $this->scheduleUnit;
    }

    public function isScheduledWeekDays(): bool
    {
        return SchedulerEnum::DAY_WEEK_DAYS === $this->scheduleDay;
    }
}
