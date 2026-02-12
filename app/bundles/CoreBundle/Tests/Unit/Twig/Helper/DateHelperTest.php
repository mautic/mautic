<?php

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    private DateHelper $helper;

    /**
     * @var string
     */
    private static $oldTimezone;

    /**
     * @var CoreParametersHelper&MockObject
     */
    private MockObject $coreParametersHelper;

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
            ->willReturnCallback(function ($key, $parameters = []) {
                switch ($key) {
                    case 'mautic.core.date.years.ago':
                        return $parameters['%count%'].' year(s) ago';
                    case 'mautic.core.date.years.in':
                        return 'in '.$parameters['%count%'].' year(s)';
                    case 'mautic.core.date.months.ago':
                        return $parameters['%count%'].' month(s) ago';
                    case 'mautic.core.date.months.in':
                        return 'in '.$parameters['%count%'].' month(s)';
                    case 'mautic.core.date.days.ago':
                        return $parameters['%count%'].' day(s) ago';
                    case 'mautic.core.date.days.in':
                        return 'in '.$parameters['%count%'].' day(s)';
                    case 'mautic.core.date.hours.ago':
                        return $parameters['%count%'].' hour(s) ago';
                    case 'mautic.core.date.hours.in':
                        return 'in '.$parameters['%count%'].' hour(s)';
                    case 'mautic.core.date.minutes.ago':
                        return $parameters['%count%'].' minute(s) ago';
                    case 'mautic.core.date.minutes.in':
                        return 'in '.$parameters['%count%'].' minute(s)';
                    case 'mautic.core.date.just.now':
                        return 'just now';
                    case 'mautic.core.date.today':
                        return 'Today';
                    default:
                        return $key;
                }
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
        $dateTimeHelperMock = $this->createMock(\Mautic\CoreBundle\Helper\DateTimeHelper::class);
        $dateTimeHelperMock->expects($this->once())
            ->method('getTextDate')
            ->willReturn('today');
        $dateTimeHelperMock->expects($this->once())
            ->method('getLocalDateTime')
            ->willReturn($now);

        // Inject the mock DateTimeHelper into DateHelper
        $reflectionProperty = new \ReflectionProperty(DateHelper::class, 'helper');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->helper, $dateTimeHelperMock);

        $result = $this->helper->toText($now);

        // Assertions
        $this->assertEquals('Today', $result);
        $this->assertStringStartsWith('Today', $result);
        $this->assertStringEndsWith('Today', $result);
    }

    public function testFullConcat(): void
    {
        $this->setDefaultLocalTimezone('Europe/Paris');
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2021-02-21 18:00:00', new \DateTimeZone('UTC'));
        $result   = $this->helper->toFullConcat($dateTime, 'UTC');
        $this->assertEquals($result, 'February 21, 2021 7:00 pm');
    }

    public function testToHumanized(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        // Test empty datetime
        $this->assertEquals('', $this->helper->toHumanized(''));

        // Test "just now" - should be within a minute
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->assertEquals('just now', $this->helper->toHumanized($now));

        // Test minutes ago - use 10 minutes to avoid timing issues
        $tenMinutesAgo = new \DateTime('-10 minutes', new \DateTimeZone('UTC'));
        $result        = $this->helper->toHumanized($tenMinutesAgo);
        $this->assertStringContainsString('minute(s) ago', $result);
        $this->assertMatchesRegularExpression('/\d+ minute\(s\) ago/', $result);

        // Test hours ago
        $twoHoursAgo = new \DateTime('-2 hours', new \DateTimeZone('UTC'));
        $this->assertEquals('2 hour(s) ago', $this->helper->toHumanized($twoHoursAgo));

        // Test days ago
        $threeDaysAgo = new \DateTime('-3 days', new \DateTimeZone('UTC'));
        $this->assertEquals('3 day(s) ago', $this->helper->toHumanized($threeDaysAgo));

        // Test months ago
        $fourMonthsAgo = new \DateTime('-4 months', new \DateTimeZone('UTC'));
        $this->assertEquals('4 month(s) ago', $this->helper->toHumanized($fourMonthsAgo));

        // Test years ago
        $oneYearAgo = new \DateTime('-1 year', new \DateTimeZone('UTC'));
        $this->assertEquals('1 year(s) ago', $this->helper->toHumanized($oneYearAgo));

        // Test minutes in future - use 10 minutes to avoid timing issues
        $tenMinutesIn = new \DateTime('+10 minutes', new \DateTimeZone('UTC'));
        $resultIn     = $this->helper->toHumanized($tenMinutesIn);
        $this->assertStringContainsString('minute(s)', $resultIn);
        $this->assertStringContainsString('in', $resultIn);
        $this->assertMatchesRegularExpression('/in \d+ minute\(s\)/', $resultIn);

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
        $tenMinutesAgo->modify('-10 minutes');
        $dateString = $tenMinutesAgo->format('Y-m-d H:i:s');
        $result     = $this->helper->toHumanized($dateString, 'UTC');
        $this->assertStringContainsString('minute(s) ago', $result);
        $this->assertMatchesRegularExpression('/\d+ minute\(s\) ago/', $result);

        $tenMinutesIn = clone $now;
        $tenMinutesIn->modify('+10 minutes');
        $dateStringIn = $tenMinutesIn->format('Y-m-d H:i:s');
        $resultIn     = $this->helper->toHumanized($dateStringIn, 'UTC');
        $this->assertStringContainsString('minute(s)', $resultIn);
        $this->assertStringContainsString('in', $resultIn);
        $this->assertMatchesRegularExpression('/in \d+ minute\(s\)/', $resultIn);
    }

    public function testToHumanizedWithDifferentTimezone(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        // Test with different timezone - use 10 minutes to avoid timing issues
        $now           = new \DateTime('now', new \DateTimeZone('America/New_York'));
        $tenMinutesAgo = clone $now;
        $tenMinutesAgo->modify('-10 minutes');
        $result = $this->helper->toHumanized($tenMinutesAgo, 'America/New_York');
        $this->assertStringContainsString('minute(s) ago', $result);
        $this->assertMatchesRegularExpression('/\d+ minute\(s\) ago/', $result);

        $tenMinutesIn = clone $now;
        $tenMinutesIn->modify('+10 minutes');
        $resultIn = $this->helper->toHumanized($tenMinutesIn, 'America/New_York');
        $this->assertStringContainsString('minute(s)', $resultIn);
        $this->assertStringContainsString('in', $resultIn);
        $this->assertMatchesRegularExpression('/in \d+ minute\(s\)/', $resultIn);
    }

    public function testToHumanizedWithCustomFormat(): void
    {
        $this->setDefaultLocalTimezone('UTC');
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // Test with custom format - use 10 minutes to avoid timing issues
        $tenMinutesAgo = clone $now;
        $tenMinutesAgo->modify('-10 minutes');
        $dateString = $tenMinutesAgo->format('Y/m/d H:i');
        $result     = $this->helper->toHumanized($dateString, 'UTC', 'Y/m/d H:i');
        $this->assertStringContainsString('minute(s) ago', $result);
        $this->assertMatchesRegularExpression('/\d+ minute\(s\) ago/', $result);

        $tenMinutesIn = clone $now;
        $tenMinutesIn->modify('+10 minutes');
        $dateStringIn = $tenMinutesIn->format('Y/m/d H:i');
        $resultIn     = $this->helper->toHumanized($dateStringIn, 'UTC', 'Y/m/d H:i');
        $this->assertStringContainsString('minute(s)', $resultIn);
        $this->assertStringContainsString('in', $resultIn);
        $this->assertMatchesRegularExpression('/in \d+ minute\(s\)/', $resultIn);
    }

    private function setDefaultLocalTimezone(string $timezone): void
    {
        $reflectedClass    = new \ReflectionClass($this->helper);
        $reflectedProperty = $reflectedClass->getProperty('helper');
        $reflectedProperty->setAccessible(true);
        $dateTimeHelper     = $reflectedProperty->getValue($this->helper);
        $reflectedClass     = new \ReflectionClass($dateTimeHelper);
        $reflectedProperty2 = $reflectedClass->getProperty('defaultLocalTimezone');
        $reflectedProperty2->setAccessible(true);
        $reflectedProperty2->setValue($dateTimeHelper, $timezone);
    }
}
