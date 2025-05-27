<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\LineChart;

#[\PHPUnit\Framework\Attributes\CoversClass(LineChart::class)]
class LineChartTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('The getUnitInterval returns the correct interval objects')]
    public function testGetUnitInterval(): void
    {
        $units = [
            'Y' => new \DateInterval('P1Y'),
            'm' => new \DateInterval('P1M'),
            'W' => new \DateInterval('P1W'),
            'd' => new \DateInterval('P1D'),
            'H' => new \DateInterval('PT1H'),
            'i' => new \DateInterval('PT1M'),
            's' => new \DateInterval('PT1S'),
        ];

        foreach ($units as $unit => $expected) {
            $chart    = new LineChart($unit, new \DateTime(), new \DateTime());
            $interval = $chart->getUnitInterval();
            $this->assertEquals($expected, $interval);
        }
    }
}
