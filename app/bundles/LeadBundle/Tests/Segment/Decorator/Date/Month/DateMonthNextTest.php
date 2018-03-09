<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Month;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthNext;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

class DateMonthNextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthNext::getOperator
     */
    public function testGetOperatorBetween()
    {
        $dateDecorator        = $this->createMock(DateDecorator::class);
        $dateOptionParameters = $this->createMock(DateOptionParameters::class);

        $dateOptionParameters->method('isBetweenRequired')
            ->willReturn(true);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $filterDecorator = new DateMonthNext($dateDecorator, $dateOptionParameters);

        $this->assertEquals('like', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthNext::getOperator
     */
    public function testGetOperatorLessOrEqual()
    {
        $dateDecorator        = $this->createMock(DateDecorator::class);
        $dateOptionParameters = $this->createMock(DateOptionParameters::class);

        $dateDecorator->method('getOperator')
            ->with()
            ->willReturn('==<<'); //Test that value is really returned from Decorator

        $dateOptionParameters->method('isBetweenRequired')
            ->willReturn(false);

        $filter        = [
            'operator' => '=<',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateMonthNext($dateDecorator, $dateOptionParameters);

        $this->assertEquals('==<<', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthNext::getParameterValue
     */
    public function testGetParameterValueBetween()
    {
        $dateDecorator        = $this->createMock(DateDecorator::class);
        $dateOptionParameters = $this->createMock(DateOptionParameters::class);

        $dateOptionParameters->method('isBetweenRequired')
            ->willReturn(true);

        $date = new DateTimeHelper('', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $filterDecorator = new DateMonthNext($dateDecorator, $dateOptionParameters);

        $expectedDate = new \DateTime('first day of next month');

        $this->assertEquals($expectedDate->format('Y-m-%'), $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthNext::getParameterValue
     */
    public function testGetParameterValueSingle()
    {
        $dateDecorator        = $this->createMock(DateDecorator::class);
        $dateOptionParameters = $this->createMock(DateOptionParameters::class);

        $dateOptionParameters->method('isBetweenRequired')
            ->willReturn(false);

        $date = new DateTimeHelper('', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $filterDecorator = new DateMonthNext($dateDecorator, $dateOptionParameters);

        $expectedDate = new \DateTime('first day of next month');

        $this->assertEquals($expectedDate->format('Y-m-d'), $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }
}
