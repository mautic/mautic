<?php

namespace Mautic\ReportBundle\Scheduler\Entity;

use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;

class SchedulerEntity implements SchedulerInterface
{
    /**
     * @var bool
     */
    private $isScheduled = false;

    /**
     * @var string|null
     */
    private $scheduleUnit;

    /**
     * @var string|null
     */
    private $scheduleDay;

    /**
     * @var string|null
     */
    private $scheduleMonthFrequency;

    /**
     * @var string|null
     */
    private $scheduleTimezone;

    /**
     * @var string|null
     */
    private $scheduleTime;

    public function __construct(bool $isScheduled, ?string $scheduleUnit, ?string $scheduleDay, ?string $scheduleMonthFrequency, ?string $scheduleTimezone = 'UTC', ?string $scheduleTime = '00:00')
    {
        $this->isScheduled            = $isScheduled;
        $this->scheduleUnit           = $scheduleUnit;
        $this->scheduleDay            = $scheduleDay;
        $this->scheduleMonthFrequency = $scheduleMonthFrequency;
        $this->scheduleTimezone       = $scheduleTimezone;
        $this->scheduleTime           = $scheduleTime;
    }

    public function isScheduled(): bool
    {
        return $this->isScheduled;
    }

    public function getScheduleUnit(): ?string
    {
        return $this->scheduleUnit;
    }

    public function getScheduleDay(): ?string
    {
        return $this->scheduleDay;
    }

    public function getScheduleMonthFrequency(): ?string
    {
        return $this->scheduleMonthFrequency;
    }

    public function isScheduledNow(): bool
    {
        return SchedulerEnum::UNIT_NOW === $this->getScheduleUnit();
    }

    public function isScheduledDaily(): bool
    {
        return SchedulerEnum::UNIT_DAILY === $this->getScheduleUnit();
    }

    public function isScheduledWeekly(): bool
    {
        return SchedulerEnum::UNIT_WEEKLY === $this->getScheduleUnit();
    }

    public function isScheduledMonthly(): bool
    {
        return SchedulerEnum::UNIT_MONTHLY === $this->getScheduleUnit();
    }

    public function isScheduledWeekDays(): bool
    {
        return SchedulerEnum::DAY_WEEK_DAYS === $this->getScheduleDay();
    }

    public function getScheduleTimezone(): ?string
    {
        return $this->scheduleTimezone;
    }

    public function getScheduleTime(): ?string
    {
        return $this->scheduleTime;
    }
}
