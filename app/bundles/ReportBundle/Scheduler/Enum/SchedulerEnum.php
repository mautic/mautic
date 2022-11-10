<?php

namespace Mautic\ReportBundle\Scheduler\Enum;

class SchedulerEnum
{
    const UNIT_NOW     = 'NOW';
    const UNIT_DAILY   = 'DAILY';
    const UNIT_WEEKLY  = 'WEEKLY'; //Defined in report.js too
    const UNIT_MONTHLY = 'MONTHLY'; //Defined in report.js too

    const DAY_MO        = 'MO';
    const DAY_TU        = 'TU';
    const DAY_WE        = 'WE';
    const DAY_TH        = 'TH';
    const DAY_FR        = 'FR';
    const DAY_SA        = 'SA';
    const DAY_SU        = 'SU';
    const DAY_WEEK_DAYS = 'WEEK_DAYS';

    const MONTH_FREQUENCY_FIRST = '1';
    const MONTH_FREQUENCY_LAST  = '-1';

    /**
     * @return array
     */
    public static function getUnitEnumForSelect()
    {
        return [
            'mautic.report.schedule.unit.now'   => self::UNIT_NOW,
            'mautic.report.schedule.unit.day'   => self::UNIT_DAILY,
            'mautic.report.schedule.unit.week'  => self::UNIT_WEEKLY,
            'mautic.report.schedule.unit.month' => self::UNIT_MONTHLY,
        ];
    }

    /**
     * @return array
     */
    public static function getDayEnumForSelect()
    {
        return [
            'mautic.report.schedule.day.monday'    => self::DAY_MO,
            'mautic.report.schedule.day.tuesday'   => self::DAY_TU,
            'mautic.report.schedule.day.wednesday' => self::DAY_WE,
            'mautic.report.schedule.day.thursday'  => self::DAY_TH,
            'mautic.report.schedule.day.friday'    => self::DAY_FR,
            'mautic.report.schedule.day.saturday'  => self::DAY_SA,
            'mautic.report.schedule.day.sunday'    => self::DAY_SU,
            'mautic.report.schedule.day.week_days' => self::DAY_WEEK_DAYS,
        ];
    }

    /**
     * @return array
     */
    public static function getMonthFrequencyForSelect()
    {
        return [
            'mautic.report.schedule.month_frequency.first' => self::MONTH_FREQUENCY_FIRST,
            'mautic.report.schedule.month_frequency.last'  => self::MONTH_FREQUENCY_LAST,
        ];
    }

    /**
     * @return array
     */
    public static function getWeekDays()
    {
        return [
            self::DAY_MO,
            self::DAY_TU,
            self::DAY_WE,
            self::DAY_TH,
            self::DAY_FR,
        ];
    }
}
