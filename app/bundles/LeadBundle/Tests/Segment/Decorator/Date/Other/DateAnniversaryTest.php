<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Other;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateAnniversary;
use Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

#[\PHPUnit\Framework\Attributes\CoversClass(DateAnniversary::class)]
class DateAnniversaryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetOperator(): void
    {
        $dateDecorator             = $this->createMock(DateDecorator::class);
        $timezoneResolver          = $this->createMock(TimezoneResolver::class);

        $filter        = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $filterDecorator = new DateAnniversary($dateDecorator, $dateOptionParameters);

        $this->assertEquals('like', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    public function testGetParameterValue(): void
    {
        /**
         * Today in '%-m-d%' format. This matches date and datetime fields.
         */
        $expectedResult = '%'.(new \DateTime('now', new \DateTimeZone('UTC')))->format('-m-d').'%';

        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $timezoneResolver->method('getDefaultDate')
            ->with(false)
            ->willReturn(
                new DateTimeHelper(
                    new \DateTime('midnight today', new \DateTimeZone('UTC')), null, 'UTC')
            );

        $filter        = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $filterDecorator = new DateAnniversary($dateDecorator, $dateOptionParameters);

        $this->assertEquals($expectedResult, $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueWithRelativeDate(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $timezoneResolver->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter        = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filter        = [
            'filter'   => 'birthday +2days',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateAnniversary($dateDecorator, $dateOptionParameters);

        $this->assertEquals('%-03-04%', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetWhereReturnsCompositeExpression(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $filter                    = ['field' => 'last_active'];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $dateOptionParameters = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->with($contactSegmentFilterCrate)
            ->willReturn(CompositeExpression::and('expr1', 'expr2'));

        $filterDecorator = new DateAnniversary($dateDecorator, $dateOptionParameters);

        $this->assertInstanceOf(
            CompositeExpression::class,
            $filterDecorator->getWhere($contactSegmentFilterCrate)
        );
    }

    public function testGetWhereReturnsString(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $filter                    = ['field' => 'last_active'];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        // Configure to return a string
        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->willReturn('WHERE clause');

        $filterDecorator = new DateAnniversary($dateDecorator, $dateOptionParameters);
        $this->assertSame('WHERE clause', $filterDecorator->getWhere($contactSegmentFilterCrate));
    }

    public function testGetWhereReturnsNull(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $filter                    = ['field' => 'last_active'];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        // Configure to return null
        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->willReturn(null);

        $filterDecorator = new DateAnniversary($dateDecorator, $dateOptionParameters);
        $this->assertNull($filterDecorator->getWhere($contactSegmentFilterCrate));
    }
}
