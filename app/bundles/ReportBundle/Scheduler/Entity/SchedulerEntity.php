<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @var null|string
     */
    private $scheduleUnit;

    /**
     * @var null|string
     */
    private $scheduleDay;

    /**
     * @var null|string
     */
    private $scheduleMonthFrequency;

    public function __construct($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency)
    {
        $this->isScheduled            = $isScheduled;
        $this->scheduleUnit           = $scheduleUnit;
        $this->scheduleDay            = $scheduleDay;
        $this->scheduleMonthFrequency = $scheduleMonthFrequency;
    }

    /**
     * @return bool
     */
    public function isScheduled()
    {
        return $this->isScheduled;
    }

    /**
     * @return null|string
     */
    public function getScheduleUnit()
    {
        return $this->scheduleUnit;
    }

    /**
     * @return null|string
     */
    public function getScheduleDay()
    {
        return $this->scheduleDay;
    }

    /**
     * @return null|string
     */
    public function getScheduleMonthFrequency()
    {
        return $this->scheduleMonthFrequency;
    }

    public function isScheduledDaily()
    {
        return $this->getScheduleUnit() === SchedulerEnum::UNIT_DAILY;
    }

    public function isScheduledWeekly()
    {
        return $this->getScheduleUnit() === SchedulerEnum::UNIT_WEEKLY;
    }

    public function isScheduledMonthly()
    {
        return $this->getScheduleUnit() === SchedulerEnum::UNIT_MONTHLY;
    }

    public function isScheduledWeekDays()
    {
        return $this->getScheduleDay() === SchedulerEnum::DAY_WEEK_DAYS;
    }
}
