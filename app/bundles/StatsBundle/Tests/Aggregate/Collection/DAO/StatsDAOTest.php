<?php

declare(strict_types=1);

namespace Mautic\StatsBundle\Tests\Aggregate\Collection\DAO;

use Mautic\StatsBundle\Aggregate\Collection\DAO\StatsDAO;
use PHPUnit\Framework\TestCase;

final class StatsDAOTest extends TestCase
{
    public function testGetYearsReturnsYears(): void
    {
        $expected = [
            2018,
            2019,
        ];

        $stats = $this->getStats()->getYears();
        $this->assertSame($expected, array_keys($stats));
    }

    public function testGetMonthsReturnsFlattenedMonths(): void
    {
        $expected = [
            '2018-12',
            '2019-11',
            '2019-12',
        ];

        $stats = $this->getStats()->getMonths();
        $this->assertSame($expected, array_keys($stats));
    }

    public function testGetWeekReturnsFlattenedMonths(): void
    {
        $expected = [
            '2018-49',
            '2019-45',
            '2019-49',
        ];

        $stats = $this->getStats()->getWeeks();
        $this->assertSame($expected, array_keys($stats));
    }

    public function testGetDaysReturnsFlattenedDays(): void
    {
        $expected = [
            '2018-12-07',
            '2019-11-07',
            '2019-11-08',
            '2019-12-07',
        ];

        $stats = $this->getStats()->getDays();
        $this->assertSame($expected, array_keys($stats));
    }

    public function testGetHoursReturnsFlattenedHours(): void
    {
        $expected = [
            '2018-12-07 12',
            '2018-12-07 13',
            '2018-12-07 14',
            '2019-11-07 12',
            '2019-11-08 12',
            '2019-12-07 12',
        ];

        $stats = $this->getStats()->getHours();
        $this->assertSame($expected, array_keys($stats));
    }

    private function getStats(): StatsDAO
    {
        $stats = new StatsDAO();

        $stats->getYear(2019)
            ->getMonth(11)
            ->getDay(8)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(14)
            ->setCount(300);

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(13)
            ->setCount(200);

        $stats->getYear(2019)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2019)
            ->getMonth(11)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        return $stats;
    }
}
