<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Scheduler\Entity;

use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;

final class SchedulerEntity implements SchedulerInterface
{
    /**
     * @param bool $isScheduled
     */
    public function __construct(
        private $isScheduled,
        private readonly ?string $scheduleUnit,
        private readonly ?string $scheduleDay,
        private readonly ?string $scheduleMonthFrequency,
    ) {
    }

    /**
     * @return bool
     */
    public function isScheduled()
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
