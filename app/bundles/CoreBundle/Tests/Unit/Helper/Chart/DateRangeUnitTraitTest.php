<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\DateRangeUnitTrait;
use PHPUnit\Framework\TestCase;

class DateRangeUnitTraitTest extends TestCase
{
    /**
     * @var DateRangeUnitTrait
     */
    private $trait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trait = $this->getMockForTrait(DateRangeUnitTrait::class);
    }

    public function testGetTimeUnitFromDateRangeWithSameDay()
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2019-04-01');
        $this->assertSame('H', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThanDay()
    {
        $from = new \DateTime('2019-04-01 00:00:00');
        $to   = new \DateTime('2019-04-01 04:30:00');
        $this->assertSame('H', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThanHour()
    {
        $from = new \DateTime('2019-04-01 04:00:00');
        $to   = new \DateTime('2019-04-01 04:30:00');
        $this->assertSame('i', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThanMinute()
    {
        $from = new \DateTime('2019-04-01 04:00:00');
        $to   = new \DateTime('2019-04-01 04:00:30');
        $this->assertSame('i', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThanMonth()
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2019-04-30');
        $this->assertSame('d', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThan100Days()
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2019-05-30');
        $this->assertSame('W', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithLessThan1000Days()
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2020-05-30');
        $this->assertSame('m', $this->trait->getTimeUnitFromDateRange($from, $to));
    }

    public function testGetTimeUnitFromDateRangeWithMoreThan1000Days()
    {
        $from = new \DateTime('2019-04-01');
        $to   = new \DateTime('2022-05-30');
        $this->assertSame('Y', $this->trait->getTimeUnitFromDateRange($from, $to));
    }
}
