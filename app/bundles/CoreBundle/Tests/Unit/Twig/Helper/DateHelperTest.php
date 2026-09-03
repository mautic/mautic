<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class DateHelperTest extends TestCase
{
    private TranslatorInterface&MockObject $translator;

    private DateHelper $helper;

    private static string $oldTimezone;

    private CoreParametersHelper&MockObject $coreParametersHelper;

    private const TEN_MINUTES_AGO    = '-10 minutes';

    private const TEN_MINUTES_IN     = '+10 minutes';

    private const MINUTES_AGO        = 'minute(s) ago';

    private const MINUTES            = 'minute(s)';

    private const REGEX_MINUTES_AGO  = '/\d+ minute\(s\) ago/';

    private const REGEX_MINUTES_IN   = '/in \d+ minute\(s\)/';

    private const TIMEZONE_NEW_YORK  = 'America/New_York';

    private const DATE_FORMAT_CUSTOM = 'Y/m/d H:i';

    public static function setUpBeforeClass(): void
    {
        self::$oldTimezone = date_default_timezone_get();
    }

    public static function tearDownAfterClass(): void
    {
        date_default_timezone_set(self::$oldTimezone);
    }

    protected function setUp(): void
    {
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->helper               = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $this->translator,
            $this->coreParametersHelper
        );

        // Setup translator mock for humanized dates
        $this->translator->method('trans')
            ->willReturnCallback(fn (string $key, array $parameters = []): string => match ($key) {
                'mautic.core.date.years.ago'   => $parameters['%count%'].' year(s) ago',
                'mautic.core.date.years.in'    => 'in '.$parameters['%count%'].' year(s)',
                'mautic.core.date.months.ago'  => $parameters['%count%'].' month(s) ago',
                'mautic.core.date.months.in'   => 'in '.$parameters['%count%'].' month(s)',
                'mautic.core.date.days.ago'    => $parameters['%count%'].' day(s) ago',
                'mautic.core.date.days.in'     => 'in '.$parameters['%count%'].' day(s)',
                'mautic.core.date.hours.ago'   => $parameters['%count%'].' hour(s) ago',
                'mautic.core.date.hours.in'    => 'in '.$parameters['%count%'].' hour(s)',
                'mautic.core.date.minutes.ago' => $parameters['%count%'].' minute(s) ago',
                'mautic.core.date.minutes.in'  => 'in '.$parameters['%count%'].' minute(s)',
                'mautic.core.date.just.now'    => 'just now',
                'mautic.core.date.today'       => 'Today',
                default                         => $key,
            });
    }

    public function testStringToText(): void
    {
        $this->setDefaultLocalTimezone('Etc/GMT-4');
        $time = '2016-01-27 14:30:00';
        $this->assertSame('January 27, 2016 6:30 pm', $this->helper->toText($time, 'UTC', 'Y-m-d H:i:s', true));
    }

    public function testStringToTextUtc(): void
    {
        $this->setDefaultLocalTimezone('UTC');
        $time = '2016-01-27 14:30:00';

        $this->assertSame('January 27, 2016 2:30 pm', $this->helper->toText($time, 'UTC', 'Y-m-d H:i:s', true));
    }

    public function testDateTimeToText(): void
    {
        $this->setDefaultLocalTimezone('Etc/GMT-4');
        $dateTime = new \DateTime('2016-01-27 14:30:00', new \DateTimeZone('UTC'));
        $this->assertSame('January 27, 2016 6:30 pm', $this->helper->toText($dateTime, 'UTC', 'Y-m-d H:i:s', true));
    }

    public function testDateTimeToTextUtc(): void
    {
        $this->setDefaultLocalTimezone('UTC');
        $dateTime = new \DateTime('2016-01-27 14:30:00', new \DateTimeZone('UTC'));

        $this->assertSame('January 27, 2016 2:30 pm', $this->helper->toText($dateTime, 'UTC', 'Y-m-d H:i:s', true));
    }

    public function testToTextWithConfigurationToTime(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('date_format_timeonly')
            ->willReturn('H:i:s');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.date.today', $this->anything())
            ->willReturn('Today');

        // Create a DateTime object for "now"
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // Create a mock for DateTimeHelper
        $dateTimeHelperMock = $this->createMock(DateTimeHelper::class);
        $dateTimeHelperMock->expects($this->once())
            ->method('getTextDate')
            ->willReturn('today');
        $dateTimeHelperMock->expects($this->once())
            ->method('getLocalDateTime')
            ->willReturn($now);

        // Inject the mock DateTimeHelper into DateHelper
        $helper = $this->createDateHelperWithDateTimeHelper($dateTimeHelperMock);

        $result = $helper->toText($now);

        $this->assertSame('Today', $result);
        $this->assertStringStartsWith('Today', $result);
        $this->assertStringEndsWith('Today', $result);
    }

    public function testFullConcat(): void
    {
        $this->setDefaultLocalTimezone('Europe/Paris');
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2021-02-21 18:00:00', new \DateTimeZone('UTC'));
        $result   = $this->helper->toFullConcat($dateTime, 'UTC');
        $this->assertSame('February 21, 2021 7:00 pm', $result);
    }

    public function testToHumanized(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        // Test empty datetime
        $this->assertSame('', $this->helper->toHumanized(''));

        // Test "just now" - should be within a minute
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->assertSame('just now', $this->helper->toHumanized($now));

        // Test minutes ago - use 10 minutes to avoid timing issues
        $tenMinutesAgo = new \DateTime(self::TEN_MINUTES_AGO, new \DateTimeZone('UTC'));
        $result        = $this->helper->toHumanized($tenMinutesAgo);
        $this->assertStringContainsString(self::MINUTES_AGO, $result);
        $this->assertMatchesRegularExpression(self::REGEX_MINUTES_AGO, $result);

        // Test hours ago
        $twoHoursAgo = new \DateTime('-2 hours', new \DateTimeZone('UTC'));
        $this->assertSame('2 hour(s) ago', $this->helper->toHumanized($twoHoursAgo));

        // Test days ago
        $threeDaysAgo = new \DateTime('-3 days', new \DateTimeZone('UTC'));
        $this->assertSame('3 day(s) ago', $this->helper->toHumanized($threeDaysAgo));

        // Test months ago
        $monthAnchor   = new \DateTime('first day of this month 00:00:00', new \DateTimeZone('UTC'));
        $fourMonthsAgo = (clone $monthAnchor)->modify('-4 months');
        $this->assertSame('4 month(s) ago', $this->helper->toHumanized($fourMonthsAgo));

        // Test years ago
        $oneYearAgo = (clone $monthAnchor)->modify('-1 year');
        $this->assertSame('1 year(s) ago', $this->helper->toHumanized($oneYearAgo));

        // Test minutes in future - use 10 minutes to avoid timing issues
        $tenMinutesIn = new \DateTime(self::TEN_MINUTES_IN, new \DateTimeZone('UTC'));
        $resultIn     = $this->helper->toHumanized($tenMinutesIn);
        $this->assertStringContainsString(self::MINUTES, $resultIn);
        $this->assertStringContainsString('in', $resultIn);
        $this->assertMatchesRegularExpression(self::REGEX_MINUTES_IN, $resultIn);

        // Test hours in future - use 3 hours to avoid timing issues
        $threeHoursIn  = new \DateTime('+3 hours', new \DateTimeZone('UTC'));
        $resultHoursIn = $this->helper->toHumanized($threeHoursIn);
        $this->assertStringContainsString('hour(s)', $resultHoursIn);
        $this->assertStringContainsString('in', $resultHoursIn);
        $this->assertMatchesRegularExpression('/in \d+ hour\(s\)/', $resultHoursIn);

        // Test days in future - use flexible assertion
        $threeDaysIn  = new \DateTime('+3 days', new \DateTimeZone('UTC'));
        $resultDaysIn = $this->helper->toHumanized($threeDaysIn);
        $this->assertStringContainsString('day(s)', $resultDaysIn);
        $this->assertStringContainsString('in', $resultDaysIn);
        $this->assertMatchesRegularExpression('/in \d+ day\(s\)/', $resultDaysIn);

        // Test months in future - use flexible assertion
        $fourMonthsIn   = new \DateTime('+4 months', new \DateTimeZone('UTC'));
        $resultMonthsIn = $this->helper->toHumanized($fourMonthsIn);
        $this->assertStringContainsString('month(s)', $resultMonthsIn);
        $this->assertStringContainsString('in', $resultMonthsIn);
        $this->assertMatchesRegularExpression('/in \d+ month\(s\)/', $resultMonthsIn);

        // Test years in future - use 2 years to ensure it's always calculated as years
        $twoYearsIn   = new \DateTime('+2 years', new \DateTimeZone('UTC'));
        $resultYearIn = $this->helper->toHumanized($twoYearsIn);
        $this->assertStringContainsString('year(s)', $resultYearIn);
        $this->assertStringContainsString('in', $resultYearIn);
        $this->assertMatchesRegularExpression('/in \d+ year\(s\)/', $resultYearIn);
    }

    public function testToHumanizedWithStringInput(): void
    {
        $this->setDefaultLocalTimezone('UTC');
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // Test with string datetime - use 10 minutes to avoid timing issues
        $tenMinutesAgo = clone $now;
        $tenMinutesAgo->modify(self::TEN_MINUTES_AGO);
        $dateString = $tenMinutesAgo->format('Y-m-d H:i:s');
        $result     = $this->helper->toHumanized($dateString, 'UTC');
        $this->assertStringContainsString(self::MINUTES_AGO, $result);
        $this->assertMatchesRegularExpression(self::REGEX_MINUTES_AGO, $result);

        $tenMinutesIn = clone $now;
        $tenMinutesIn->modify(self::TEN_MINUTES_IN);
        $dateStringIn = $tenMinutesIn->format('Y-m-d H:i:s');
        $resultIn     = $this->helper->toHumanized($dateStringIn, 'UTC');
        $this->assertStringContainsString(self::MINUTES, $resultIn);
        $this->assertStringContainsString('in', $resultIn);
        $this->assertMatchesRegularExpression(self::REGEX_MINUTES_IN, $resultIn);
    }

    public function testToHumanizedWithDifferentTimezone(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        // Test with different timezone - use 10 minutes to avoid timing issues
        $now           = new \DateTime('now', new \DateTimeZone(self::TIMEZONE_NEW_YORK));
        $tenMinutesAgo = clone $now;
        $tenMinutesAgo->modify(self::TEN_MINUTES_AGO);
        $result = $this->helper->toHumanized($tenMinutesAgo, self::TIMEZONE_NEW_YORK);
        $this->assertStringContainsString(self::MINUTES_AGO, $result);
        $this->assertMatchesRegularExpression(self::REGEX_MINUTES_AGO, $result);

        $tenMinutesIn = clone $now;
        $tenMinutesIn->modify(self::TEN_MINUTES_IN);
        $resultIn = $this->helper->toHumanized($tenMinutesIn, self::TIMEZONE_NEW_YORK);
        $this->assertStringContainsString(self::MINUTES, $resultIn);
        $this->assertStringContainsString('in', $resultIn);
        $this->assertMatchesRegularExpression(self::REGEX_MINUTES_IN, $resultIn);
    }

    public function testToHumanizedWithCustomFormat(): void
    {
        $this->setDefaultLocalTimezone('UTC');
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // Test with custom format - use 10 minutes to avoid timing issues
        $tenMinutesAgo = clone $now;
        $tenMinutesAgo->modify(self::TEN_MINUTES_AGO);
        $dateString = $tenMinutesAgo->format(self::DATE_FORMAT_CUSTOM);
        $result     = $this->helper->toHumanized($dateString, 'UTC', self::DATE_FORMAT_CUSTOM);
        $this->assertStringContainsString(self::MINUTES_AGO, $result);
        $this->assertMatchesRegularExpression(self::REGEX_MINUTES_AGO, $result);

        $tenMinutesIn = clone $now;
        $tenMinutesIn->modify(self::TEN_MINUTES_IN);
        $dateStringIn = $tenMinutesIn->format(self::DATE_FORMAT_CUSTOM);
        $resultIn     = $this->helper->toHumanized($dateStringIn, 'UTC', self::DATE_FORMAT_CUSTOM);
        $this->assertStringContainsString(self::MINUTES, $resultIn);
        $this->assertStringContainsString('in', $resultIn);
        $this->assertMatchesRegularExpression(self::REGEX_MINUTES_IN, $resultIn);
    }

    public function testToTextShortWithToday(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        // Create a mock for DateTimeHelper to return 'today'
        $dateTimeHelperMock = $this->createMock(DateTimeHelper::class);
        $dateTimeHelperMock->expects($this->once())
            ->method('getTextDate')
            ->willReturn('today');

        // Inject the mock DateTimeHelper into DateHelper
        $helper = $this->createDateHelperWithDateTimeHelper($dateTimeHelperMock);

        $now    = new \DateTime('now', new \DateTimeZone('UTC'));
        $result = $helper->toTextShort($now);

        $this->assertSame('Today', $result);
    }

    public function testToTextShortWithOlderDate(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        // Create a mock for DateTimeHelper to return false (not today/yesterday)
        $dateTimeHelperMock = $this->createMock(DateTimeHelper::class);
        $dateTimeHelperMock->expects($this->once())
            ->method('getTextDate')
            ->willReturn(false);
        // Mock toLocalString() which is called by format() when getTextDate returns false
        $dateTimeHelperMock->expects($this->once())->method('toLocalString')
            ->willReturn('December 31, 2023');

        // Inject the mock DateTimeHelper into DateHelper
        $helper = $this->createDateHelperWithDateTimeHelper($dateTimeHelperMock);

        $olderDate = '2023-12-31 23:59:59';
        $result    = $helper->toTextShort($olderDate, 'UTC', 'Y-m-d H:i:s');

        $this->assertStringContainsString('2023', $result);
        $this->assertStringContainsString('December', $result);
    }

    public function testToFullDoesNotMutatePassedDateTimeTimezone(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        $dateTime = new \DateTime('2026-12-15 19:00:00', new \DateTimeZone('Europe/Berlin'));
        $this->helper->toFull($dateTime);

        $this->assertSame('Europe/Berlin', $dateTime->getTimezone()->getName());
        $this->assertSame('2026-12-15 19:00:00', $dateTime->format('Y-m-d H:i:s'));
    }

    public function testToTextShortWithEmptyDateTime(): void
    {
        $result = $this->helper->toTextShort('');
        $this->assertSame('', $result);
    }

    private function createDateHelperWithDateTimeHelper(DateTimeHelper $dateTimeHelper): DateHelper
    {
        return new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $this->translator,
            $this->coreParametersHelper,
            $dateTimeHelper,
        );
    }

    private function setDefaultLocalTimezone(string $timezone): void
    {
        $reflectedClass     = new \ReflectionClass($this->helper);
        $reflectedProperty  = $reflectedClass->getProperty('helper');
        $dateTimeHelper     = $reflectedProperty->getValue($this->helper);
        ReflectionHelper::setValue($dateTimeHelper, 'defaultLocalTimezone', $timezone);
    }
}
