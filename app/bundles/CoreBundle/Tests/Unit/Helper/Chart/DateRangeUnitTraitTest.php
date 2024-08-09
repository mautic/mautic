<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\DateRangeUnitTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateRangeUnitTraitTest extends TestCase
{
    /**
     * @var MockObject|DateRangeUnitTrait
     */
    private MockObject $trait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trait = $this->getMockForTrait(DateRangeUnitTrait::class);
    }

    public function testGetTimeUnitFromDateRangeWithSameDay(): void
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2019-04-01');
        $this->assertSame('H', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThanDay(): void
    {
        $from = new \DateTime('2019-04-01 00:00:00');
        $to   = new \DateTime('2019-04-01 04:30:00');
        $this->assertSame('H', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThanHour(): void
    {
        $from = new \DateTime('2019-04-01 04:00:00');
        $to   = new \DateTime('2019-04-01 04:30:00');
        $this->assertSame('i', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThanMinute(): void
    {
        $from = new \DateTime('2019-04-01 04:00:00');
        $to   = new \DateTime('2019-04-01 04:00:30');
        $this->assertSame('i', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThanMonth(): void
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2019-04-30');
        $this->assertSame('d', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThan100Days(): void
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2019-05-30');
        $this->assertSame('W', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThan1000Days(): void
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2020-05-30');
        $this->assertSame('m', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithMoreThan1000Days(): void
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2022-05-30');
        $this->assertSame('Y', $this->trait->getTimeUnitFromDateRange($from, $to));
    }
}
