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
                    case 'mautic.core.date.months.ago':
                        return $parameters['%count%'].' month(s) ago';
                    case 'mautic.core.date.days.ago':
                        return $parameters['%count%'].' day(s) ago';
                    case 'mautic.core.date.hours.ago':
                        return $parameters['%count%'].' hour(s) ago';
                    case 'mautic.core.date.minutes.ago':
                        return $parameters['%count%'].' minute(s) ago';
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

        // Test "just now"
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->assertEquals('just now', $this->helper->toHumanized($now));

        // Test minutes ago
        $fiveMinutesAgo = $now->modify('-5 minutes');
        $this->assertEquals('5 minute(s) ago', $this->helper->toHumanized($fiveMinutesAgo));

        // Test hours ago
        $twoHoursAgo = $now->modify('-2 hours');
        $this->assertEquals('2 hour(s) ago', $this->helper->toHumanized($twoHoursAgo));

        // Test days ago
        $threeDaysAgo = $now->modify('-3 days');
        $this->assertEquals('3 day(s) ago', $this->helper->toHumanized($threeDaysAgo));

        // Test months ago
        $fourMonthsAgo = $now->modify('-4 months');
        $this->assertEquals('4 month(s) ago', $this->helper->toHumanized($fourMonthsAgo), print_r($fourMonthsAgo, true));

        // Test years ago
        $oneYearAgo = $now->modify('-1 year');
        $this->assertEquals('1 year(s) ago', $this->helper->toHumanized($oneYearAgo));
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
