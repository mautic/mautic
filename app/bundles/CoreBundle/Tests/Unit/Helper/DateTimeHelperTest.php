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
        $this->expectNotToPerformAssertions();
        DateTimeHelper::validateMysqlDateTimeUnit('s');
        DateTimeHelper::validateMysqlDateTimeUnit('i');
        DateTimeHelper::validateMysqlDateTimeUnit('H');
        DateTimeHelper::validateMysqlDateTimeUnit('d');
        DateTimeHelper::validateMysqlDateTimeUnit('W');
        DateTimeHelper::validateMysqlDateTimeUnit('m');
        DateTimeHelper::validateMysqlDateTimeUnit('Y');
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

    public function testAddMethodModifiesOriginalDateTime(): void
    {
        $originalDate   = '2023-01-01 12:00:00';
        $intervalString = 'P1D'; // Interval of 1 day

        // Initialize DateTimeHelper with a specific date
        $dateTimeHelper = new DateTimeHelper($originalDate, DateTimeHelper::FORMAT_DB, 'UTC');

        // Add interval to the original DateTime object
        $dateTimeHelper->add($intervalString);

        // Get the modified DateTime object
        $modifiedDateTime = $dateTimeHelper->getDateTime();

        // Assert that the date has been modified correctly
        $this->assertEquals('2023-01-02 12:00:00', $modifiedDateTime->format(DateTimeHelper::FORMAT_DB));
    }

    public function testAddMethodReturnsClonedDateTime(): void
    {
        $originalDate   = '2023-01-01 12:00:00';
        $intervalString = 'P1D'; // Interval of 1 day

        // Initialize DateTimeHelper with a specific date
        $dateTimeHelper = new DateTimeHelper($originalDate, DateTimeHelper::FORMAT_DB, 'UTC');

        // Add interval to a clone of the original DateTime object
        $clonedDateTime = $dateTimeHelper->add($intervalString, true);

        // Get the original DateTime object
        $originalDateTime = $dateTimeHelper->getDateTime();

        // Assert that the clone has been modified correctly
        $this->assertEquals('2023-01-02 12:00:00', $clonedDateTime->format(DateTimeHelper::FORMAT_DB));

        // Assert that the original DateTime object remains unchanged
        $this->assertEquals($originalDate, $originalDateTime->format(DateTimeHelper::FORMAT_DB));
    }

    public function testSubMethodModifiesOriginalDateTime(): void
    {
        $originalDate   = '2023-01-02 12:00:00';
        $intervalString = 'P1D'; // Interval of 1 day

        // Initialize DateTimeHelper with a specific date
        $dateTimeHelper = new DateTimeHelper($originalDate, DateTimeHelper::FORMAT_DB, 'UTC');

        // Subtract interval from the original DateTime object
        $dateTimeHelper->sub($intervalString);

        // Get the modified DateTime object
        $modifiedDateTime = $dateTimeHelper->getDateTime();

        // Assert that the date has been modified correctly
        $this->assertEquals('2023-01-01 12:00:00', $modifiedDateTime->format(DateTimeHelper::FORMAT_DB));
    }

    public function testSubMethodReturnsClonedDateTime(): void
    {
        $originalDate   = '2023-01-02 12:00:00';
        $intervalString = 'P1D'; // Interval of 1 day

        // Initialize DateTimeHelper with a specific date
        $dateTimeHelper = new DateTimeHelper($originalDate, DateTimeHelper::FORMAT_DB, 'UTC');

        // Subtract interval from a clone of the original DateTime object
        $clonedDateTime = $dateTimeHelper->sub($intervalString, true);

        // Get the original DateTime object
        $originalDateTime = $dateTimeHelper->getDateTime();

        // Assert that the clone has been modified correctly
        $this->assertEquals('2023-01-01 12:00:00', $clonedDateTime->format(DateTimeHelper::FORMAT_DB));

        // Assert that the original DateTime object remains unchanged
        $this->assertEquals($originalDate, $originalDateTime->format(DateTimeHelper::FORMAT_DB));
    }

    public function testModifyMethodModifiesOriginalDateTime(): void
    {
        $originalDate       = '2023-01-02 12:00:00';
        $modificationString = '+1 day';

        // Initialize DateTimeHelper with a specific date
        $dateTimeHelper = new DateTimeHelper($originalDate, DateTimeHelper::FORMAT_DB, 'UTC');

        // Modify the original DateTime object
        $dateTimeHelper->modify($modificationString);

        // Get the modified DateTime object
        $modifiedDateTime = $dateTimeHelper->getDateTime();

        // Assert that the date has been modified correctly
        $this->assertEquals('2023-01-03 12:00:00', $modifiedDateTime->format(DateTimeHelper::FORMAT_DB));
    }

    public function testModifyMethodReturnsClonedDateTime(): void
    {
        $originalDate       = '2023-01-02 12:00:00';
        $modificationString = '+1 day';

        // Initialize DateTimeHelper with a specific date
        $dateTimeHelper = new DateTimeHelper($originalDate, DateTimeHelper::FORMAT_DB, 'UTC');

        // Modify a clone of the original DateTime object
        $clonedDateTime = $dateTimeHelper->modify($modificationString, true);

        // Get the original DateTime object
        $originalDateTime = $dateTimeHelper->getDateTime();

        // Assert that the clone has been modified correctly
        $this->assertEquals('2023-01-03 12:00:00', $clonedDateTime->format(DateTimeHelper::FORMAT_DB));

        // Assert that the original DateTime object remains unchanged
        $this->assertEquals($originalDate, $originalDateTime->format(DateTimeHelper::FORMAT_DB));
    }
}
