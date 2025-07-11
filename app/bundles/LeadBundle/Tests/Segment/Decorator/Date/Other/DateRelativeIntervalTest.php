<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Other;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval;
use Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

#[\PHPUnit\Framework\Attributes\CoversClass(DateRelativeInterval::class)]
class DateRelativeIntervalTest extends \PHPUnit\Framework\TestCase
{
    public function testGetOperatorEqual(): void
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

    public function testGetOperatorNotEqual(): void
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

    public function testGetOperatorLessOrEqual(): void
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

    public function testGetParameterValuePlusDaysWithGreaterOperator(): void
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

    public function testGetParameterValueMinusMonthWithNotEqualOperator(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '-3 months', $dateOptionParameters);

        $this->assertEquals('2017-12-02%', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueDaysAgoWithNotEqualOperator(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '5 days ago', $dateOptionParameters);

        $this->assertEquals('2018-02-25%', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueYearsAgoWithGreaterOperator(): void
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

    public function testGetParameterValueDaysWithEqualOperator(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '5 days', $dateOptionParameters);

        $this->assertEquals('2018-03-07%', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetWhereReturnsCompositeExpression(): void
    {
        $dateDecorator        = $this->createMock(DateDecorator::class);
        $timezoneResolver     = $this->createMock(TimezoneResolver::class);
        $filterCrate          = new ContactSegmentFilterCrate(['operator' => '=']);
        $dateOptionParameters = new DateOptionParameters($filterCrate, [], $timezoneResolver);

        // Mock CompositeExpression return
        $composite = CompositeExpression::and('field = 1', 'field = 2');
        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->with($filterCrate)
            ->willReturn($composite);

        $decorator = new DateRelativeInterval($dateDecorator, '+5 days', $dateOptionParameters);
        $result    = $decorator->getWhere($filterCrate);

        $this->assertInstanceOf(CompositeExpression::class, $result);
        $this->assertSame($composite, $result);
    }

    public function testGetWhereReturnsString(): void
    {
        $dateDecorator        = $this->createMock(DateDecorator::class);
        $timezoneResolver     = $this->createMock(TimezoneResolver::class);
        $filterCrate          = new ContactSegmentFilterCrate(['operator' => '=']);
        $dateOptionParameters = new DateOptionParameters($filterCrate, [], $timezoneResolver);

        // Mock string return
        $expectedWhere = "date_field > '2023-01-01'";
        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->willReturn($expectedWhere);

        $decorator = new DateRelativeInterval($dateDecorator, '+5 days', $dateOptionParameters);
        $this->assertSame($expectedWhere, $decorator->getWhere($filterCrate));
    }

    public function testGetWhereReturnsNull(): void
    {
        $dateDecorator        = $this->createMock(DateDecorator::class);
        $timezoneResolver     = $this->createMock(TimezoneResolver::class);
        $filterCrate          = new ContactSegmentFilterCrate(['operator' => '=']);
        $dateOptionParameters = new DateOptionParameters($filterCrate, [], $timezoneResolver);

        // Mock null return
        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->willReturn(null);

        $decorator = new DateRelativeInterval($dateDecorator, '+5 days', $dateOptionParameters);
        $this->assertNull($decorator->getWhere($filterCrate));
    }
}
