<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Helper;

use Mautic\ReportBundle\Helper\RelativeDateHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RelativeDateHelperTest extends TestCase
{
    #[DataProvider('provideRelativeValues')]
    public function testRelativeValueDetection(string $value): void
    {
        $this->assertTrue(RelativeDateHelper::isRelative($value));
    }

    public static function provideRelativeValues(): \Generator
    {
        yield ['tomorrow'];
        yield ['last month'];
        yield ['-3 months 2 days'];
        yield ['5 days ago'];
        yield ['first day of next month'];
        yield ['birthday +2days'];
    }

    public function testAbsoluteAndInvalidValuesAreNotRelative(): void
    {
        $this->assertFalse(RelativeDateHelper::isRelative('2026-09-03'));
        $this->assertFalse(RelativeDateHelper::isRelative('+invalid'));
    }

    public function testNamedMonthSpansTheMonth(): void
    {
        $range = RelativeDateHelper::resolveRange('this month', true);

        $this->assertStringEndsWith('-01', $range['start']);
        $this->assertNotSame($range['start'], $range['end']);
    }

    public function testFirstDayExpressionSpansOneDay(): void
    {
        $range = RelativeDateHelper::resolveRange('first day of next month', true);

        $this->assertSame($range['start'], $range['end']);
    }
}
