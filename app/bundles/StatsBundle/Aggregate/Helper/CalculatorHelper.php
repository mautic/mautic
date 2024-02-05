<?php

namespace Mautic\StatsBundle\Aggregate\Helper;

use Mautic\StatsBundle\Aggregate\Collection\DAO\StatDAO;

class CalculatorHelper
{
    /**
     * @throws \Exception
     */
    public static function getYearLabel($year, $labelFormat): string
    {
        return (new \DateTime(self::getYearDateString($year)))->format($labelFormat);
    }

    public static function getYearDateString($year): string
    {
        return "$year-01-01 00:00:00";
    }

    /**
     * @param string $lastYear
     * @param string $thisYear
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public static function fillInMissingYears(StatDAO $statDAO, $lastYear, $thisYear, $labelFormat): void
    {
        if (!$lastYear) {
            return;
        }

        $lastYear = new \DateTime(self::getYearDateString($lastYear));
        $thisYear = new \DateTime(self::getYearDateString($thisYear));

        if (!isset($statDAO->getStats()[$lastYear->format($labelFormat)])) {
            $statDAO->addStat($lastYear->format($labelFormat), 0);
        }

        while ($lastYear < $thisYear) {
            $lastYear->modify('+1 year');
            $statDAO->addStat($lastYear->format($labelFormat), 0);
        }
    }

    /**
     * @throws \Exception
     */
    public static function getMonthLabel($month, $labelFormat): string
    {
        return (new \DateTime(self::getMonthDateString($month)))->format($labelFormat);
    }

    public static function getMonthDateString($month): string
    {
        return "$month-01 00:00:00";
    }

    /**
     * @param string $lastMonth
     * @param string $thisMonth
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public static function fillInMissingMonths(StatDAO $statDAO, $lastMonth, $thisMonth, $labelFormat): void
    {
        if (!$lastMonth) {
            return;
        }

        $lastMonth = new \DateTime(self::getMonthDateString($lastMonth));
        $thisMonth = new \DateTime(self::getMonthDateString($thisMonth));

        if (!isset($statDAO->getStats()[$lastMonth->format($labelFormat)])) {
            $statDAO->addStat($lastMonth->format($labelFormat), 0);
        }

        while ($lastMonth < $thisMonth) {
            $lastMonth->modify('+1 month');
            $statDAO->addStat($lastMonth->format($labelFormat), 0);
        }
    }

    /**
     * @throws \Exception
     */
    public static function getDayLabel($day, $labelFormat): string
    {
        return (new \DateTime(self::getDayDateString($day)))->format($labelFormat);
    }

    public static function getDayDateString($day): string
    {
        return "$day 00:00:00";
    }

    /**
     * @param string $date
     *
     * @throws \Exception
     */
    public static function getWeekFromDayString($date): string
    {
        return (new \DateTime($date))->format('Y-W');
    }

    /**
     * @param string $date
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public static function getWeekLabel($date, $labelFormat = 'Y-W'): string
    {
        return (new \DateTime(self::getWeekDateString($date)))->format($labelFormat);
    }

    public static function getWeekDateString($date): string
    {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})$/', $date, $matches)) {
            throw new \InvalidArgumentException('Invalid argument, Y-W format is required.');
        }
        $year = $matches[1];
        $week = $matches[2];

        return date('Y-m-d 00:00:00', strtotime($year.'W'.$week.'1'));
    }

    /**
     * @param string $yesterday
     * @param string $today
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public static function fillInMissingWeeks(StatDAO $statDAO, $yesterday, $today, $labelFormat): void
    {
        if (!$yesterday) {
            return;
        }

        $yesterday = new \DateTime(self::getWeekDateString($yesterday));
        $today     = new \DateTime(self::getWeekDateString($today));

        while ($yesterday < $today) {
            $statDAO->addStat($yesterday->format($labelFormat), 0);
            $yesterday->modify('+1 week');
        }
    }

    /**
     * @param string $yesterday
     * @param string $today
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public static function fillInMissingDays(StatDAO $statDAO, $yesterday, $today, $labelFormat): void
    {
        if (!$yesterday) {
            return;
        }

        $yesterday = (new \DateTime(self::getDayDateString($yesterday)))->modify('-1 day');
        $today     = new \DateTime(self::getDayDateString($today));

        while ($yesterday < $today) {
            $yesterday->modify('+1 day');
            $statDAO->addStat($yesterday->format($labelFormat), 0);
        }
    }

    /**
     * @throws \Exception
     */
    public static function getHourLabel($hour, $labelFormat): string
    {
        return (new \DateTime(self::getHourDateString($hour)))->format($labelFormat);
    }

    public static function getHourDateString($hour): string
    {
        return "$hour:00:00";
    }

    /**
     * @param string $lastHour
     * @param string $thisHour
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public static function fillInMissingHours(StatDAO $statDAO, $lastHour, $thisHour, $labelFormat): void
    {
        if (!$lastHour) {
            return;
        }

        $lastHour = new \DateTime(self::getHourDateString($lastHour));
        $thisHour = new \DateTime(self::getHourDateString($thisHour));

        while ($lastHour < $thisHour) {
            $lastHour->modify('+1 hour');
            $statDAO->addStat($lastHour->format($labelFormat), 0);
        }
    }
}
