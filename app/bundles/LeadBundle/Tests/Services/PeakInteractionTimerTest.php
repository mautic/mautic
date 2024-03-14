<?php

namespace Mautic\LeadBundle\Tests\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Services\PeakInteractionTimer;
use Mautic\PageBundle\Entity\HitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TestablePeakInteractionTimer extends PeakInteractionTimer
{
    private \DateTime $testTime;

    public function setCurrentDateTime(\DateTime $dateTime): void
    {
        $this->testTime = $dateTime;
    }

    protected function getCurrentDateTime(\DateTimeZone $timezone): \DateTime
    {
        return $this->testTime;
    }
}

class PeakInteractionTimerTest extends TestCase
{
    private MockObject|CoreParametersHelper $coreParametersHelperMock;
    private MockObject|StatRepository $statRepositoryMock;
    private MockObject|HitRepository $hitRepositoryMock;

    private string $defaultTimezone = 'UTC';

    protected function setUp(): void
    {
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->statRepositoryMock       = $this->createMock(StatRepository::class);
        $this->hitRepositoryMock        = $this->createMock(HitRepository::class);

        // Set the expected default timezone
        $this->coreParametersHelperMock
            ->method('get')
            ->with('default_timezone')
            ->willReturn($this->defaultTimezone);
    }

    /**
     * @dataProvider dateTimeWithTimezoneProvider
     */
    public function testGetDefaultOptimalTime(string $currentDate, string $expectedDate, ?string $contactTimezone = null): void
    {
        $contactMock = $this->createMock(Lead::class);
        if ($contactTimezone) {
            $contactMock->method('getTimezone')->willReturn($contactTimezone);
        }
        $contactTimezone = $contactTimezone ?: $this->defaultTimezone;

        $this->statRepositoryMock
            ->method('getLeadStats')
            ->willReturn([]);
        $this->hitRepositoryMock
            ->method('getLeadHits')
            ->willReturn([]);

        // Create an instance of the testable PeakInteractionTimer
        $testableTimer = new TestablePeakInteractionTimer($this->coreParametersHelperMock, $this->statRepositoryMock, $this->hitRepositoryMock);

        // Set the current time to a fixed value for testing
        $fixedCurrentTime = new \DateTime($currentDate, new \DateTimeZone($contactTimezone));
        $testableTimer->setCurrentDateTime($fixedCurrentTime);

        // Call getOptimalTime on the testable instance
        $optimalTime = $testableTimer->getOptimalTime($contactMock);

        // Assert that the returned DateTimeInterface is in the contact's timezone
        $this->assertEquals($contactTimezone ?: $this->defaultTimezone, $optimalTime->getTimezone()->getName(), 'The optimal time should be in the contact\'s timezone.');
        $this->assertEquals($expectedDate, $optimalTime->format('Y-m-d H:i:s'));
    }

    public static function dateTimeWithTimezoneProvider(): iterable
    {
        // If current time is optimal then return the same datetime
        yield ['2024-03-12 10:22:11', '2024-03-12 10:22:11', 'America/New_York'];

        // If current time is before the optimal window, then schedule at first optimal hour
        yield ['2024-03-12 05:30:00', '2024-03-12 09:00:00', 'Asia/Tokyo'];

        // If current time is after the optimal window, then schedule on the next day
        yield ['2024-03-12 21:37:00', '2024-03-13 09:00:00', 'Europe/Warsaw'];

        // Without contact's preferred timezone
        yield ['2024-03-12 11:00:00', '2024-03-12 11:00:00', null];
    }

    /**
     * @dataProvider dateTimeAndDayWithTimezoneProvider
     */
    public function testGetDefaultOptimalTimeAndDay(string $currentDate, string $expectedDate, ?string $contactTimezone = null): void
    {
        $contactMock = $this->createMock(Lead::class);
        if ($contactTimezone) {
            $contactMock->method('getTimezone')->willReturn($contactTimezone);
        }
        $contactTimezone = $contactTimezone ?: $this->defaultTimezone;

        // Create an instance of the testable PeakInteractionTimer
        $testableTimer = new TestablePeakInteractionTimer($this->coreParametersHelperMock, $this->statRepositoryMock, $this->hitRepositoryMock);

        // Set the current time to a fixed value for testing
        $fixedCurrentTime = new \DateTime($currentDate, new \DateTimeZone($contactTimezone));
        $testableTimer->setCurrentDateTime($fixedCurrentTime);

        // Call getOptimalTimeAndDay on the testable instance
        $optimalTimeAndDay = $testableTimer->getOptimalTimeAndDay($contactMock);

        // Assert that the returned DateTimeInterface is in the contact's timezone
        $this->assertEquals($contactTimezone ?: $this->defaultTimezone, $optimalTimeAndDay->getTimezone()->getName(), 'The optimal time and day should be in the contact\'s timezone.');

        // Assert that the date and time are as expected
        $this->assertEquals($expectedDate, $optimalTimeAndDay->format('Y-m-d H:i:s'), 'The optimal time and day should match the expected value.');
    }

    public static function dateTimeAndDayWithTimezoneProvider(): iterable
    {
        // If current time and day are optimal then return the same datetime
        yield ['2024-03-12 10:22:11', '2024-03-12 10:22:11', 'America/New_York']; // Tuesday

        // If current time is before the optimal window but on an optimal day, then schedule at first optimal hour
        yield ['2024-03-11 05:30:00', '2024-03-11 09:00:00', 'Asia/Tokyo']; // Monday

        // If current time is after the optimal window and today is not an optimal day, then schedule on the next optimal day
        yield ['2024-03-10 21:37:00', '2024-03-11 09:00:00', 'Europe/Warsaw']; // Sunday to Monday

        // If current day is an optimal day but time is after the optimal window, then schedule on the next optimal day
        yield ['2024-03-11 21:37:00', '2024-03-12 09:00:00', 'Europe/Warsaw']; // Monday to Tuesday

        // Without contact's preferred timezone, on a non-optimal day
        yield ['2024-03-10 11:00:00', '2024-03-11 09:00:00', null]; // Sunday to Monday

        // Without contact's preferred timezone, on an optimal day but after the optimal time
        yield ['2024-03-14 13:00:00', '2024-03-18 09:00:00', null]; // Thursday to Monday
    }
}
