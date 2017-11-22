<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Entity;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\ScheduleNotValidException;

class ReportTest extends \PHPUnit_Framework_TestCase
{
    public function testNotScheduled()
    {
        $report = $this->getInvalidReport();

        $report->setAsNotScheduled();

        $this->assertFalse($report->isScheduled());
        $this->assertNull($report->getToAddress());
        $this->assertNull($report->getScheduleUnit());
        $this->assertNull($report->getScheduleDay());
        $this->assertNull($report->getScheduleMonthFrequency());
    }

    public function testDailyScheduled()
    {
        $report = $this->getInvalidReport();

        $report->ensureIsDailyScheduled();

        $this->assertTrue($report->isScheduled());
        $this->assertSame('xxx', $report->getToAddress());
        $this->assertSame('DAILY', $report->getScheduleUnit());
        $this->assertNull($report->getScheduleDay());
        $this->assertNull($report->getScheduleMonthFrequency());
    }

    public function testWeeklyScheduled()
    {
        $report = $this->getInvalidReport();
        $report->setScheduleDay('WEEK_DAYS');

        $report->ensureIsWeeklyScheduled();
        $this->assertTrue($report->isScheduled());
        $this->assertSame('xxx', $report->getToAddress());
        $this->assertSame(SchedulerEnum::UNIT_WEEKLY, $report->getScheduleUnit());
        $this->assertSame(SchedulerEnum::DAY_WEEK_DAYS, $report->getScheduleDay());
        $this->assertNull($report->getScheduleMonthFrequency());
    }

    public function testMonthlyScheduled()
    {
        $report = $this->getInvalidReport();
        $report->setScheduleDay('WEEK_DAYS');
        $report->setScheduleMonthFrequency('-1');

        $report->ensureIsMonthlyScheduled();
        $this->assertTrue($report->isScheduled());
        $this->assertSame('xxx', $report->getToAddress());
        $this->assertSame(SchedulerEnum::UNIT_MONTHLY, $report->getScheduleUnit());
        $this->assertSame(SchedulerEnum::DAY_WEEK_DAYS, $report->getScheduleDay());
        $this->assertSame(SchedulerEnum::MONTH_FREQUENCY_LAST, $report->getScheduleMonthFrequency());
    }

    public function testInvalidMonthlyScheduled()
    {
        $this->expectException(ScheduleNotValidException::class);

        $report = $this->getInvalidReport();
        $report->ensureIsMonthlyScheduled();
    }

    public function testInvalidWeeklyScheduled()
    {
        $this->expectException(ScheduleNotValidException::class);

        $report = $this->getInvalidReport();
        $report->ensureIsWeeklyScheduled();
    }

    /**
     * @return Report
     */
    private function getInvalidReport()
    {
        $report = new Report();
        $report->setIsScheduled(true);
        $report->setToAddress('xxx');
        $report->setScheduleUnit('unit');
        $report->setScheduleDay('day');
        $report->setScheduleMonthFrequency('frequency');

        return $report;
    }
}
