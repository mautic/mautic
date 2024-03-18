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
        return clone $this->testTime;
    }
}

class PeakInteractionTimerTest extends TestCase
{
    private MockObject|CoreParametersHelper $coreParametersHelperMock;

    /**
     * @var StatRepository|MockObject
     */
    private $statRepositoryMock;

    /**
     * @var MockObject|HitRepository
     */
    private $hitRepositoryMock;

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
     * @dataProvider defaultDateTimeWithTimezoneProvider
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

    /**
     * @return iterable<array<int, string|null>>
     */
    public static function defaultDateTimeWithTimezoneProvider(): iterable
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
     * @dataProvider defaultDateTimeAndDayWithTimezoneProvider
     */
    public function testGetDefaultOptimalTimeAndDay(string $currentDate, string $expectedDate, ?string $contactTimezone = null): void
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

        // Call getOptimalTimeAndDay on the testable instance
        $optimalTimeAndDay = $testableTimer->getOptimalTimeAndDay($contactMock);

        // Assert that the returned DateTimeInterface is in the contact's timezone
        $this->assertEquals($contactTimezone ?: $this->defaultTimezone, $optimalTimeAndDay->getTimezone()->getName(), 'The optimal time and day should be in the contact\'s timezone.');

        // Assert that the date and time are as expected
        $this->assertEquals($expectedDate, $optimalTimeAndDay->format('Y-m-d H:i:s'), 'The optimal time and day should match the expected value.');
    }

    /**
     * @return iterable<array<int, string|null>>
     */
    public static function defaultDateTimeAndDayWithTimezoneProvider(): iterable
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

    /**
     * @param array<int, array<string, string|\DateTime|\DateInterval>> $emailReads
     * @param array<int, array<string, string|\DateTime|null>>          $pageHits
     *
     * @dataProvider getOptimalTimeDataProvider
     */
    public function testGetOptimalTime(string $currentDate, string $expectedDate, array $emailReads, array $pageHits): void
    {
        $contactMock = $this->createMock(Lead::class);

        $this->statRepositoryMock
            ->method('getLeadStats')
            ->willReturn($emailReads);
        $this->hitRepositoryMock
            ->method('getLeadHits')
            ->willReturn($pageHits);

        // Create an instance of the testable PeakInteractionTimer
        $testableTimer = new TestablePeakInteractionTimer($this->coreParametersHelperMock, $this->statRepositoryMock, $this->hitRepositoryMock);

        // Set the current time to a fixed value for testing
        $fixedCurrentTime = new \DateTime($currentDate, new \DateTimeZone($this->defaultTimezone));
        $testableTimer->setCurrentDateTime($fixedCurrentTime);

        // Call getOptimalTime on the testable instance
        $optimalTime = $testableTimer->getOptimalTime($contactMock);

        $this->assertEquals($expectedDate, $optimalTime->format('Y-m-d H:i:s'));
    }

    /**
     * @return iterable<array<int, mixed>>
     */
    public static function getOptimalTimeDataProvider(): iterable
    {
        $emailReads = [
            PeakInteractionTimerTest::getEmailReadData('2023-09-02 13:20:17', '2023-09-02 11:45:32'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-05 14:02:45', '2023-09-05 10:38:09'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-08 15:40:15', '2023-09-08 10:18:22'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-11 17:52:18', '2023-09-11 09:33:47'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-14 14:20:17', '2023-09-14 08:45:32'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-17 14:02:45', '2023-09-17 11:38:09'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-20 15:40:15', '2023-09-20 09:18:22'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-23 13:52:18', '2023-09-23 08:33:47'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-26 14:20:17', '2023-09-26 10:45:32'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-29 14:02:45', '2023-09-29 11:38:09'),
        ];

        $pageHits = [
            PeakInteractionTimerTest::getPageHitData('2023-09-02 13:36:32'),
            PeakInteractionTimerTest::getPageHitData('2023-09-05 14:12:39'),
            PeakInteractionTimerTest::getPageHitData('2023-09-08 15:28:50'),
            PeakInteractionTimerTest::getPageHitData('2023-09-11 17:40:11'),
            PeakInteractionTimerTest::getPageHitData('2023-09-14 14:20:23'),
            PeakInteractionTimerTest::getPageHitData('2023-09-17 14:45:45'),
            PeakInteractionTimerTest::getPageHitData('2023-09-20 15:10:59'),
            PeakInteractionTimerTest::getPageHitData('2023-09-23 13:55:30'),
            PeakInteractionTimerTest::getPageHitData('2023-09-26 14:30:17'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:10:58'),
        ];
        // Previously defined email reads and page views should result in the following preferences:
        // Optimal time for interactions: 13 - 16

        // If current time is optimal then return the same datetime
        yield ['2023-10-01 14:22:33', '2023-10-01 14:22:33', $emailReads, $pageHits];

        // If current time is before the optimal window, then schedule at first optimal hour
        yield ['2023-10-01 10:22:11', '2023-10-01 13:00:00', $emailReads, $pageHits];

        // If current time is after the optimal window, then schedule on the next day
        yield ['2023-10-01 16:02:22', '2023-10-02 13:00:00', $emailReads, $pageHits];

        // Add multiple page hits within 1 hour
        // Activity within an hour should be counted as 1 interaction and not change the optimal time (13 - 16)
        yield ['2023-10-01 16:02:22', '2023-10-02 13:00:00', $emailReads, array_merge($pageHits, [
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:11:58'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:12:02'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:12:12'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:14:18'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:16:35'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:18:55'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:30:55'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:45:12'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:48:12'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:55:12'),
        ])];
    }

    /**
     * @param array<int, array<string, string|\DateTime|\DateInterval>> $emailReads
     * @param array<int, array<string, string|\DateTime|null>>          $pageHits
     *
     * @dataProvider getOptimalTimeAndDayDataProvider
     */
    public function testGetOptimalTimeAndDay(string $currentDate, string $expectedDate, array $emailReads, array $pageHits): void
    {
        $contactMock = $this->createMock(Lead::class);

        $this->statRepositoryMock
            ->method('getLeadStats')
            ->willReturn($emailReads);
        $this->hitRepositoryMock
            ->method('getLeadHits')
            ->willReturn($pageHits);

        // Create an instance of the testable PeakInteractionTimer
        $testableTimer = new TestablePeakInteractionTimer($this->coreParametersHelperMock, $this->statRepositoryMock, $this->hitRepositoryMock);

        // Set the current time to a fixed value for testing
        $fixedCurrentTime = new \DateTime($currentDate, new \DateTimeZone($this->defaultTimezone));
        $testableTimer->setCurrentDateTime($fixedCurrentTime);

        // Call getOptimalTime on the testable instance
        $optimalTime = $testableTimer->getOptimalTimeAndDay($contactMock);

        $this->assertEquals($expectedDate, $optimalTime->format('Y-m-d H:i:s'));
    }

    /**
     * @return iterable<array<int, mixed>>
     */
    public static function getOptimalTimeAndDayDataProvider(): iterable
    {
        $emailReads = [
            PeakInteractionTimerTest::getEmailReadData('2023-09-02 13:20:17', '2023-09-02 11:45:32'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-05 14:02:45', '2023-09-05 10:38:09'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-08 15:40:15', '2023-09-08 10:18:22'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-11 17:52:18', '2023-09-11 09:33:47'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-14 14:20:17', '2023-09-14 08:45:32'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-17 14:02:45', '2023-09-17 11:38:09'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-20 15:40:15', '2023-09-20 09:18:22'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-23 13:52:18', '2023-09-23 08:33:47'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-26 14:20:17', '2023-09-26 10:45:32'),
            PeakInteractionTimerTest::getEmailReadData('2023-09-29 14:02:45', '2023-09-29 11:38:09'),
        ];

        $pageHits = [
            PeakInteractionTimerTest::getPageHitData('2023-09-02 13:36:32'),
            PeakInteractionTimerTest::getPageHitData('2023-09-05 14:12:39'),
            PeakInteractionTimerTest::getPageHitData('2023-09-08 15:28:50'),
            PeakInteractionTimerTest::getPageHitData('2023-09-11 17:40:11'),
            PeakInteractionTimerTest::getPageHitData('2023-09-14 14:20:23'),
            PeakInteractionTimerTest::getPageHitData('2023-09-17 14:45:45'),
            PeakInteractionTimerTest::getPageHitData('2023-09-20 15:10:59'),
            PeakInteractionTimerTest::getPageHitData('2023-09-23 13:55:30'),
            PeakInteractionTimerTest::getPageHitData('2023-09-26 14:30:17'),
            PeakInteractionTimerTest::getPageHitData('2023-09-29 18:10:58'),
        ];
        // Previously defined email reads and page views should result in the following preferences:
        // Optimal time for interactions: 13 - 16
        // Optimal days for interactions: Sunday, Thursday, Friday

        // If current time and day is optimal then return the same datetime
        yield ['2023-10-07 14:22:33', '2023-10-07 14:22:33', $emailReads, $pageHits];

        // If current time and day is before the optimal window, then schedule at first optimal hour
        yield ['2023-10-01 10:22:11', '2023-10-03 13:00:00', $emailReads, $pageHits];

        // If current time is after the optimal window, then schedule on the next optimal day
        yield ['2023-10-08 16:02:22', '2023-10-10 13:00:00', $emailReads, $pageHits];
    }

    /**
     * @return array<string, string|\DateTime|\DateInterval>
     */
    private static function getEmailReadData(string $dateRead, string $dateSent): array
    {
        $dateReadObj   = new \DateTime($dateRead);
        $dateSentObj   = new \DateTime($dateSent);
        $timeToReadObj = $dateSentObj->diff($dateReadObj);

        return [
             'email_id'      => '36',
             'id'            => '634',
             'dateRead'      => $dateReadObj,
             'dateSent'      => $dateSentObj,
             'subject'       => 'Email subject',
             'email_name'    => 'Email name',
             'isRead'        => '1',
             'isFailed'      => '0',
             'storedSubject' => 'Email subject',
             'timeToRead'    => $timeToReadObj,
         ];
    }

    /**
     * @return array<string, string|\DateTime|null>
     */
    private static function getPageHitData(string $dateHit): array
    {
        $dateHitObj = new \DateTime($dateHit);

        return [
            'hitId'        => '247',
            'page_id'      => null,
            'userAgent'    => 'Mozilla/5.0 (X11; Debian; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/123.0',
            'dateHit'      => $dateHitObj,
            'dateLeft'     => null,
            'referer'      => 'https://mautic.ddev.site/email/view/64e8475bb5d01527171344',
            'source'       => 'email',
            'sourceId'     => '8',
            'url'          => 'https://example.com/',
            'urlTitle'     => null,
            'clientInfo'   => 'a:7:{s:4:"type";s:7:"browser";s:4:"name";s:7:"Firefox";s:10:"short_name";s:2:"FF";s:7:"version";s:5:"123.0";s:6:"engine";s:5:"Gecko";s:14:"engine_version";s:5:"109.0";s:6:"family";s:7:"Firefox";}',
            'device'       => 'desktop',
            'deviceOsName' => 'Debian',
            'deviceBrand'  => '',
            'deviceModel'  => '',
            'lead_id'      => '84',
        ];
    }
}
