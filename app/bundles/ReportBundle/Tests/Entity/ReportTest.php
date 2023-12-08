<?php

namespace Mautic\ReportBundle\Tests\Entity;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\ScheduleNotValidException;

class ReportTest extends \PHPUnit\Framework\TestCase
{
    public function testNotScheduled(): void
    {
        $report = $this->getInvalidReport();

        $report->setAsNotScheduled();

        $this->assertFalse($report->isScheduled());
        $this->assertNull($report->getToAddress());
        $this->assertNull($report->getScheduleUnit());
        $this->assertNull($report->getScheduleDay());
        $this->assertNull($report->getScheduleMonthFrequency());
    }

    public function testDailyScheduled(): void
    {
        $report = $this->getInvalidReport();

        $report->ensureIsDailyScheduled();

        $this->assertTrue($report->isScheduled());
        $this->assertSame('xxx', $report->getToAddress());
        $this->assertSame('DAILY', $report->getScheduleUnit());
        $this->assertNull($report->getScheduleDay());
        $this->assertNull($report->getScheduleMonthFrequency());
    }

    public function testWeeklyScheduled(): void
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

    public function testMonthlyScheduled(): void
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

    public function testInvalidMonthlyScheduled(): void
    {
        $this->expectException(ScheduleNotValidException::class);

        $report = $this->getInvalidReport();
        $report->ensureIsMonthlyScheduled();
    }

    public function testInvalidWeeklyScheduled(): void
    {
        $this->expectException(ScheduleNotValidException::class);

        $report = $this->getInvalidReport();
        $report->ensureIsWeeklyScheduled();
    }

    public function testGetFilterValueIfFIltersAreEmpty(): void
    {
        $report = $this->getInvalidReport();

        $this->expectException(\UnexpectedValueException::class);
        $this->assertSame('1234', $report->getFilterValue('e.test'));
    }

    public function testGetFilterValueIfExists(): void
    {
        $report = $this->getInvalidReport();
        $report->setFilters([
            [
                'column' => 'e.test',
                'value'  => '1234',
            ],
        ]);

        $this->assertSame('1234', $report->getFilterValue('e.test'));
    }

    public function testGetFilterValueIfDoesNotExist(): void
    {
        $report = $this->getInvalidReport();
        $report->setFilters([
            [
                'column' => 'e.test',
                'value'  => '1234',
            ],
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $report->getFilterValue('I need coffee');
    }

    public function testSetAsScheduledNow(): void
    {
        $email  = 'john@doe.email';
        $report = new Report();
        $report->setAsScheduledNow($email);

        $this->assertTrue($report->isScheduled());
        $this->assertSame($email, $report->getToAddress());
        $this->assertSame(SchedulerEnum::UNIT_NOW, $report->getScheduleUnit());
    }

    public function testIsScheduledNowIfNot(): void
    {
        $report = new Report();

        $this->assertFalse($report->isScheduledNow());

        $report->setScheduleUnit(SchedulerEnum::UNIT_NOW);

        $this->assertTrue($report->isScheduledNow());
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
