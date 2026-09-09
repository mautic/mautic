<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Scheduler\Enum;

final class SchedulerEnum
{
    public const string UNIT_NOW     = 'NOW';

    public const string UNIT_DAILY   = 'DAILY';

    public const string UNIT_WEEKLY  = 'WEEKLY'; // Defined in report.js too

    public const string UNIT_MONTHLY = 'MONTHLY'; // Defined in report.js too

    public const string DAY_MO        = 'MO';

    public const string DAY_TU        = 'TU';

    public const string DAY_WE        = 'WE';

    public const string DAY_TH        = 'TH';

    public const string DAY_FR        = 'FR';

    public const string DAY_SA        = 'SA';

    public const string DAY_SU        = 'SU';

    public const string DAY_WEEK_DAYS = 'WEEK_DAYS';

    public const string MONTH_FREQUENCY_FIRST = '1';

    public const string MONTH_FREQUENCY_LAST  = '-1';

    /**
     * @return array<string, string>
     */
    public static function getUnitEnumForSelect(): array
    {
        return [
            'mautic.report.schedule.unit.now'   => self::UNIT_NOW,
            'mautic.report.schedule.unit.day'   => self::UNIT_DAILY,
            'mautic.report.schedule.unit.week'  => self::UNIT_WEEKLY,
            'mautic.report.schedule.unit.month' => self::UNIT_MONTHLY,
        ];
    }

    /**
     * @return array<string, string>
     */
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

    /**
     * @return array<string, string>
     */
    public static function getMonthFrequencyForSelect(): array
    {
        return [
            'mautic.report.schedule.month_frequency.first' => self::MONTH_FREQUENCY_FIRST,
            'mautic.report.schedule.month_frequency.last'  => self::MONTH_FREQUENCY_LAST,
        ];
    }

    /**
     * @return array<int, string>
     */
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
