<?php

namespace Mautic\LeadBundle\Tests\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Services\PeakInteractionTimer;
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

    protected function setUp(): void
    {
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
    }

    /**
     * @dataProvider dateTimeWithTimezoneProvider
     */
    public function testGetDefaultOptimalTimeWithContactTimezone(string $currentDate, string $expectedDate, ?string $contactTimezone = null): void
    {
        $contactMock = $this->createMock(Lead::class);
        if ($contactTimezone) {
            $contactMock->method('getTimezone')->willReturn($contactTimezone);
        }
        $defaultTimezone = 'UTC';
        $contactTimezone = $contactTimezone ?: $defaultTimezone;

        // Set the expected default timezone
        $this->coreParametersHelperMock
            ->method('get')
            ->with('default_timezone')
            ->willReturn($defaultTimezone);

        // Create an instance of the testable PeakInteractionTimer
        $testableTimer = new TestablePeakInteractionTimer($this->coreParametersHelperMock);

        // Set the current time to a fixed value for testing
        $fixedCurrentTime = new \DateTime($currentDate, new \DateTimeZone($contactTimezone));
        $testableTimer->setCurrentDateTime($fixedCurrentTime);

        // Call getOptimalTime on the testable instance
        $optimalTime = $testableTimer->getOptimalTime($contactMock);

        // Assert that the returned DateTimeInterface is in the contact's timezone
        $this->assertEquals($contactTimezone ?: $defaultTimezone, $optimalTime->getTimezone()->getName(), 'The optimal time should be in the contact\'s timezone.');
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
}
