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
use Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthThis;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

class DateMonthThisTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthThis::getOperator
     */
    public function testGetOperatorBetween()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $filter        = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, []);

        $filterDecorator = new DateMonthThis($dateDecorator, $dateOptionParameters);

        $this->assertEquals('like', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthThis::getOperator
     */
    public function testGetOperatorLessOrEqual()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $dateDecorator->method('getOperator')
            ->with()
            ->willReturn('=<');

        $filter        = [
            'operator' => 'lte',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, []);

        $filterDecorator = new DateMonthThis($dateDecorator, $dateOptionParameters);

        $this->assertEquals('=<', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthThis::getParameterValue
     */
    public function testGetParameterValueBetween()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter        = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, []);

        $filterDecorator = new DateMonthThis($dateDecorator, $dateOptionParameters);

        $expectedDate = new \DateTime('first day of this month');

        $this->assertEquals($expectedDate->format('Y-m-%'), $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthThis::getParameterValue
     */
    public function testGetParameterValueSingle()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter        = [
            'operator' => 'lt',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, []);

        $filterDecorator = new DateMonthThis($dateDecorator, $dateOptionParameters);

        $expectedDate = new \DateTime('first day of this month');

        $this->assertEquals($expectedDate->format('Y-m-d'), $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }
}
