<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Day;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayTomorrow;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

class DateDayTomorrowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayTomorrow::getOperator
     */
    public function testGetOperatorBetween()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $filter        = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, []);

        $filterDecorator = new DateDayTomorrow($dateDecorator, $dateOptionParameters);

        $this->assertEquals('like', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayTomorrow::getOperator
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

        $filterDecorator = new DateDayTomorrow($dateDecorator, $dateOptionParameters);

        $this->assertEquals('=<', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayTomorrow::getParameterValue
     */
    public function testGetParameterValueBetween()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter        = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, []);

        $filterDecorator = new DateDayTomorrow($dateDecorator, $dateOptionParameters);

        $this->assertEquals('2018-03-03%', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayTomorrow::getParameterValue
     */
    public function testGetParameterValueSingle()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter        = [
            'operator' => 'lt',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, []);

        $filterDecorator = new DateDayTomorrow($dateDecorator, $dateOptionParameters);

        $this->assertEquals('2018-03-03', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }
}
