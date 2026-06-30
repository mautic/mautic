<?php

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\ReflectionHelper;
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
     * @var MockObject&CoreParametersHelper
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
            ->willReturnCallback(fn ($key, $parameters = []) => match ($key) {
                'mautic.core.date.years.ago'   => $parameters['%count%'].' year(s) ago',
                'mautic.core.date.months.ago'  => $parameters['%count%'].' month(s) ago',
                'mautic.core.date.days.ago'    => $parameters['%count%'].' day(s) ago',
                'mautic.core.date.hours.ago'   => $parameters['%count%'].' hour(s) ago',
                'mautic.core.date.minutes.ago' => $parameters['%count%'].' minute(s) ago',
                'mautic.core.date.just.now'    => 'just now',
                'mautic.core.date.today'       => 'Today',
                default                        => $key,
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
        ReflectionHelper::setValue($this->helper, 'helper', $dateTimeHelperMock);

        $result = $this->helper->toText($now);

        // Assertions
        $this->assertSame('Today', $result);
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
        $this->assertSame('just now', $this->helper->toHumanized($now));

        // Test minutes ago
        $fiveMinutesAgo = $now->modify('-5 minutes');
        $this->assertSame('5 minute(s) ago', $this->helper->toHumanized($fiveMinutesAgo));

        // Test hours ago
        $twoHoursAgo = $now->modify('-2 hours');
        $this->assertSame('2 hour(s) ago', $this->helper->toHumanized($twoHoursAgo));

        // Test days ago
        $threeDaysAgo = $now->modify('-3 days');
        $this->assertSame('3 day(s) ago', $this->helper->toHumanized($threeDaysAgo));

        // Test months ago
        $fourMonthsAgo = $now->modify('-4 months');
        $this->assertSame('4 month(s) ago', $this->helper->toHumanized($fourMonthsAgo), print_r($fourMonthsAgo, true));

        // Test years ago
        $oneYearAgo = $now->modify('-1 year');
        $this->assertSame('1 year(s) ago', $this->helper->toHumanized($oneYearAgo));
    }

    public function testToTextShortWithToday(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        // Create a mock for DateTimeHelper to return 'today'
        $dateTimeHelperMock = $this->createMock(\Mautic\CoreBundle\Helper\DateTimeHelper::class);
        $dateTimeHelperMock->expects($this->once())
            ->method('getTextDate')
            ->willReturn('today');

        // Inject the mock DateTimeHelper into DateHelper
        ReflectionHelper::setValue($this->helper, 'helper', $dateTimeHelperMock);

        $now    = new \DateTime('now', new \DateTimeZone('UTC'));
        $result = $this->helper->toTextShort($now);

        $this->assertSame('Today', $result);
    }

    public function testToTextShortWithOlderDate(): void
    {
        $this->setDefaultLocalTimezone('UTC');

        // Create a mock for DateTimeHelper to return false (not today/yesterday)
        $dateTimeHelperMock = $this->createMock(\Mautic\CoreBundle\Helper\DateTimeHelper::class);
        $dateTimeHelperMock->expects($this->once())
            ->method('getTextDate')
            ->willReturn(false);
        // Mock toLocalString() which is called by format() when getTextDate returns false
        $dateTimeHelperMock->method('toLocalString')
            ->willReturn('December 31, 2023');

        // Inject the mock DateTimeHelper into DateHelper
        ReflectionHelper::setValue($this->helper, 'helper', $dateTimeHelperMock);

        $olderDate = '2023-12-31 23:59:59';
        $result    = $this->helper->toTextShort($olderDate, 'UTC', 'Y-m-d H:i:s');

        // Should return formatted date
        $this->assertStringContainsString('2023', $result);
        $this->assertStringContainsString('December', $result);
    }

    public function testToTextShortWithEmptyDateTime(): void
    {
        $result = $this->helper->toTextShort('');
        $this->assertSame('', $result);
    }

    private function setDefaultLocalTimezone(string $timezone): void
    {
        $reflectedClass     = new \ReflectionClass($this->helper);
        $reflectedProperty  = $reflectedClass->getProperty('helper');
        $dateTimeHelper     = $reflectedProperty->getValue($this->helper);
        ReflectionHelper::setValue($dateTimeHelper, 'defaultLocalTimezone', $timezone);
    }
}
