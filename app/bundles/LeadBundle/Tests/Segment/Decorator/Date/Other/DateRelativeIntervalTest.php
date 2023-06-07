<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Other;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionAbstract;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval;
use Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

class DateRelativeIntervalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getOperator
     */
    public function testGetOperatorEqual()
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $filter        = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '+5 days', $dateOptionParameters);

        $this->assertEquals('like', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getOperator
     */
    public function testGetOperatorNotEqual()
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $filter        = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '+5 days', $dateOptionParameters);

        $this->assertEquals('notLike', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getOperator
     */
    public function testGetOperatorLessOrEqual()
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $dateDecorator->method('getOperator')
            ->with()
            ->willReturn('==<<'); // Test that value is really returned from Decorator

        $filter        = [
            'operator' => '=<',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '+5 days', $dateOptionParameters);

        $this->assertEquals('==<<', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValuePlusDaysWithGreaterOperator()
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '>',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '+5 days', $dateOptionParameters);

        $this->assertEquals('2018-03-07', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueMinusMonthWithNotEqualOperator()
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper();

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $interval        = '-3 months';
        $filterDecorator = new DateRelativeInterval($dateDecorator, $interval, $dateOptionParameters);
        $expectedDate    = $date->modify($interval, true);

        $this->assertEquals($expectedDate->format(DateOptionAbstract::Y_M_D), $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueDaysAgoWithNotEqualOperator()
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper();

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $originalValue   = '5 days ago';
        $filterDecorator = new DateRelativeInterval($dateDecorator, $originalValue, $dateOptionParameters);
        $expectedDate    = $date->modify($originalValue, true);

        $this->assertEquals($expectedDate->format(DateOptionAbstract::Y_M_D), $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueYearsAgoWithGreaterOperator()
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '>',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '2 years ago', $dateOptionParameters);

        $this->assertEquals('2016-03-02', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueDaysWithEqualOperator()
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper();

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $interval        = '5 days';
        $filterDecorator = new DateRelativeInterval($dateDecorator, $interval, $dateOptionParameters);
        $expectedData    = $date->modify($interval, true);

        $this->assertEquals($expectedData->format(DateOptionAbstract::Y_M_D), $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueDaysWithEqualOperatorWithDateTimeTimezone(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper('now', null, 'Europe/Paris');

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '=',
            'type'     => 'datetime',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $interval        = '+5 days';
        $filterDecorator = new DateRelativeInterval($dateDecorator, $interval, $dateOptionParameters);
        $results         = $filterDecorator->getParameterValue($contactSegmentFilterCrate);

        $endDate  = $date->toUtcString(DateOptionAbstract::Y_M_D_H_I_S);
        $date->modify('-1 day');
        $startDate = $date->toUtcString(DateOptionAbstract::Y_M_D_H_I_S);
        $this->assertEquals([$startDate, $endDate], $results);
    }
}
