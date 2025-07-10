<?php

namespace Mautic\ReportBundle\Scheduler\Enum;

class SchedulerEnum
{
    public const UNIT_NOW     = 'NOW';

    public const UNIT_DAILY   = 'DAILY';

    public const UNIT_WEEKLY  = 'WEEKLY'; // Defined in report.js too

    public const UNIT_MONTHLY = 'MONTHLY'; // Defined in report.js too

    public const DAY_MO        = 'MO';

    public const DAY_TU        = 'TU';

    public const DAY_WE        = 'WE';

    public const DAY_TH        = 'TH';

    public const DAY_FR        = 'FR';

    public const DAY_SA        = 'SA';

    public const DAY_SU        = 'SU';

    public const DAY_WEEK_DAYS = 'WEEK_DAYS';

    public const MONTH_FREQUENCY_FIRST = '1';

    public const MONTH_FREQUENCY_LAST  = '-1';

    public static function getUnitEnumForSelect(): array
    {
        return [
            'mautic.report.schedule.unit.now'   => self::UNIT_NOW,
            'mautic.report.schedule.unit.day'   => self::UNIT_DAILY,
            'mautic.report.schedule.unit.week'  => self::UNIT_WEEKLY,
            'mautic.report.schedule.unit.month' => self::UNIT_MONTHLY,
        ];
    }

    public static function getDayEnumForSelect(): array
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

    public static function getMonthFrequencyForSelect(): array
    {
        return [
            'mautic.report.schedule.month_frequency.first' => self::MONTH_FREQUENCY_FIRST,
            'mautic.report.schedule.month_frequency.last'  => self::MONTH_FREQUENCY_LAST,
        ];
    }

    public static function getWeekDays(): array
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
