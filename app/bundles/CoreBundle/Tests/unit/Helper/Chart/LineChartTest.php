<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\LineChart;

/**
 * Class ColorHelper test.
 */
class LineChartTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox The getUnitInterval returns the correct interval objects
     *
     * @covers \Mautic\CoreBundle\Helper\Chart\LineChart::getUnitInterval
     */
    public function testGetUnitInterval()
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
