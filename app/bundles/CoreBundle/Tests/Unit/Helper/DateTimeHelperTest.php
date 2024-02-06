<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Loader\ParameterLoader;

class DateTimeHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox The guessTimezoneFromOffset returns correct values
     *
     * @covers \Mautic\CoreBundle\Helper\DateTimeHelper::guessTimezoneFromOffset
     */
    public function testGuessTimezoneFromOffset(): void
    {
        $helper   = new DateTimeHelper();
        $timezone = $helper->guessTimezoneFromOffset();
        $this->assertEquals($timezone, 'Europe/London');
        $timezone = $helper->guessTimezoneFromOffset(3600);
        $this->assertEquals($timezone, 'Europe/Paris');
        $timezone = $helper->guessTimezoneFromOffset(-2 * 3600);
        $this->assertEquals($timezone, 'America/Goose_Bay'); // Is it really in timezone -2
        $timezone = $helper->guessTimezoneFromOffset(-5 * 3600);
        $this->assertEquals($timezone, 'America/New_York');
    }

    public function testBuildIntervalWithBadUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $helper = new DateTimeHelper();
        $helper->buildInterval(4, 'j');
    }

    public function testBuildIntervalWithRightUnits(): void
    {
        $helper   = new DateTimeHelper();
        $interval = $helper->buildInterval(4, 'Y');
        $this->assertEquals(new \DateInterval('P4Y'), $interval);
        $interval = $helper->buildInterval(4, 'M');
        $this->assertEquals(new \DateInterval('P4M'), $interval);
        $interval = $helper->buildInterval(4, 'I');
        $this->assertEquals(new \DateInterval('PT4M'), $interval);
        $interval = $helper->buildInterval(4, 'S');
        $this->assertEquals(new \DateInterval('PT4S'), $interval);
    }

    public function testvalidateMysqlDateTimeUnitWillThrowExceptionOnBadUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DateTimeHelper::validateMysqlDateTimeUnit('D');
    }

    public function testvalidateMysqlDateTimeUnitWillNotThrowExceptionOnExpectedUnit(): void
    {
        DateTimeHelper::validateMysqlDateTimeUnit('s');
        DateTimeHelper::validateMysqlDateTimeUnit('i');
        DateTimeHelper::validateMysqlDateTimeUnit('H');
        DateTimeHelper::validateMysqlDateTimeUnit('d');
        DateTimeHelper::validateMysqlDateTimeUnit('W');
        DateTimeHelper::validateMysqlDateTimeUnit('m');
        DateTimeHelper::validateMysqlDateTimeUnit('Y');

        $this->assertTrue(true, 'Just to avoid the risky test warning...');
    }

    public function testGetLocalTimezoneOffset(): void
    {
        $timezone = (new ParameterLoader())->getParameterBag()->get('default_timezone');
        $helper   = new DateTimeHelper('now', DateTimeHelper::FORMAT_DB, $timezone);
        $date     = new \DateTime();
        $date->setTimezone(new \DateTimeZone($timezone));
        $this->assertEquals($date->format('P'), $helper->getLocalTimezoneOffset());
    }

    public function testGetDiff(): void
    {
        // Initialize DateTimeHelper with a specific date and timezone
        $dateTimeHelper = new DateTimeHelper('2023-01-01 12:00:00', DateTimeHelper::FORMAT_DB, 'UTC');

        // Test default behavior with 'now' as compare and no format
        $interval = $dateTimeHelper->getDiff();
        $this->assertInstanceOf(\DateInterval::class, $interval);

        // Test with custom compare date and no format
        $customDate = new \DateTime('2023-01-02 12:00:00', new \DateTimeZone('UTC'));
        $interval   = $dateTimeHelper->getDiff($customDate);
        $this->assertEquals(1, $interval->days);

        // Test with custom compare date and format
        $formattedInterval = $dateTimeHelper->getDiff($customDate, '%a');
        $this->assertEquals('1', $formattedInterval);

        // Test with resetTime set to true
        $interval = $dateTimeHelper->getDiff($customDate, null, true);
        $this->assertEquals(0, $interval->h);
    }

    public function testGetDiffWithNowAndResetTime(): void
    {
        // Set a non-default timezone for the DateTimeHelper object
        $nonDefaultTimezone = new \DateTimeZone('Asia/Tokyo');
        $dateTimeHelper     = new DateTimeHelper('2023-01-01 12:00:00', DateTimeHelper::FORMAT_DB, $nonDefaultTimezone->getName());

        // Get the difference with 'now' and $resetTime set to true
        $interval = $dateTimeHelper->getDiff('now', null, true);

        // Get the current time in the non-default timezone with time reset to midnight
        $nowInNonDefaultTimezone = new \DateTime('now', $nonDefaultTimezone);
        $nowInNonDefaultTimezone->setTime(0, 0, 0);

        // Get the time from the DateTimeHelper object with time reset to midnight
        $dateTimeFromHelper = clone $dateTimeHelper->getDateTime();
        $dateTimeFromHelper->setTime(0, 0, 0);

        // Calculate the expected difference in days
        $expectedInterval = $nowInNonDefaultTimezone->diff($dateTimeFromHelper);
        $expectedDays     = (int) $expectedInterval->format('%R%a');

        // Assert that the interval days match the expected difference
        $this->assertEquals($expectedDays, (int) $interval->format('%R%a'));

        // Assert that the interval hours are zero since times were reset
        $this->assertEquals(0, $interval->h);

        // Assert that the interval has the correct timezone
        $this->assertEquals($nonDefaultTimezone->getName(), $dateTimeHelper->getDateTime()->getTimezone()->getName());
    }
}
