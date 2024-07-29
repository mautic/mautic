<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\DateExtension;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class DateExtensionTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DateHelper
     */
    private $dateHelper;

    /**
     * @var DateExtension
     */
    private $dateExtension;

    protected function setUp(): void
    {
        $this->dateHelper = $this->createMock(DateHelper::class);
        $this->dateExtension = new DateExtension($this->dateHelper);
    }

    public function testGetFunctions(): void
    {
        $functions = $this->dateExtension->getFunctions();

        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);
        $this->assertCount(8, $functions);

        $functionNames = array_map(function (TwigFunction $function) {
            return $function->getName();
        }, $functions);

        $this->assertContains('dateToText', $functionNames);
        $this->assertContains('dateToFull', $functionNames);
        $this->assertContains('dateToFullConcat', $functionNames);
        $this->assertContains('dateToDate', $functionNames);
        $this->assertContains('dateToTime', $functionNames);
        $this->assertContains('dateToShort', $functionNames);
        $this->assertContains('dateFormatRange', $functionNames);
        $this->assertContains('dateToHumanized', $functionNames);
    }

    public function testToText(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $timezone = 'UTC';
        $fromFormat = 'Y-m-d H:i:s';
        $forceDateForNonText = true;

        $this->dateHelper->expects($this->once())
            ->method('toText')
            ->with($datetime, $timezone, $fromFormat, $forceDateForNonText)
            ->willReturn('December 31, 2023 11:59 PM');

        $result = $this->dateExtension->toText($datetime, $timezone, $fromFormat, $forceDateForNonText);
        $this->assertSame('December 31, 2023 11:59 PM', $result);
    }

    public function testToHumanized(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $timezone = 'UTC';
        $fromFormat = 'Y-m-d H:i:s';

        $this->dateHelper->expects($this->once())
            ->method('toHumanized')
            ->with($datetime, $timezone, $fromFormat)
            ->willReturn('1 year ago');

        $result = $this->dateExtension->toHumanized($datetime, $timezone, $fromFormat);
        $this->assertSame('1 year ago', $result);
    }

    public function testToFull(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $timezone = 'UTC';
        $fromFormat = 'Y-m-d H:i:s';

        $this->dateHelper->expects($this->once())
            ->method('toFull')
            ->with($datetime, $timezone, $fromFormat)
            ->willReturn('December 31, 2023 11:59 PM');

        $result = $this->dateExtension->toFull($datetime, $timezone, $fromFormat);
        $this->assertSame('December 31, 2023 11:59 PM', $result);
    }

    public function testToFullConcat(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $timezone = 'UTC';
        $fromFormat = 'Y-m-d H:i:s';

        $this->dateHelper->expects($this->once())
            ->method('toFullConcat')
            ->with($datetime, $timezone, $fromFormat)
            ->willReturn('2023-12-31 11:59 PM');

        $result = $this->dateExtension->toFullConcat($datetime, $timezone, $fromFormat);
        $this->assertSame('2023-12-31 11:59 PM', $result);
    }

    public function testToDate(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $timezone = 'UTC';
        $fromFormat = 'Y-m-d H:i:s';

        $this->dateHelper->expects($this->once())
            ->method('toDate')
            ->with($datetime, $timezone, $fromFormat)
            ->willReturn('2023-12-31');

        $result = $this->dateExtension->toDate($datetime, $timezone, $fromFormat);
        $this->assertSame('2023-12-31', $result);
    }

    public function testToTime(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $timezone = 'UTC';
        $fromFormat = 'Y-m-d H:i:s';

        $this->dateHelper->expects($this->once())
            ->method('toTime')
            ->with($datetime, $timezone, $fromFormat)
            ->willReturn('23:59');

        $result = $this->dateExtension->toTime($datetime, $timezone, $fromFormat);
        $this->assertSame('23:59', $result);
    }

    public function testToShort(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $timezone = 'UTC';
        $fromFormat = 'Y-m-d H:i:s';

        $this->dateHelper->expects($this->once())
            ->method('toShort')
            ->with($datetime, $timezone, $fromFormat)
            ->willReturn('Sun, Dec 31');

        $result = $this->dateExtension->toShort($datetime, $timezone, $fromFormat);
        $this->assertSame('Sun, Dec 31', $result);
    }

    public function testFormatRange(): void
    {
        $range = new \DateInterval('P1Y2M3DT4H5M6S');

        $this->dateHelper->expects($this->once())
            ->method('formatRange')
            ->with($range)
            ->willReturn('1 year 2 months 3 days 4 hours 5 minutes 6 seconds');

        $result = $this->dateExtension->formatRange($range);
        $this->assertSame('1 year 2 months 3 days 4 hours 5 minutes 6 seconds', $result);
    }
}