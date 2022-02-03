<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayToday;
use Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayTomorrow;
use Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayYesterday;
use Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthLast;
use Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthNext;
use Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthThis;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateAnniversary;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateDefault;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval;
use Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver;
use Mautic\LeadBundle\Segment\Decorator\Date\Week\DateWeekLast;
use Mautic\LeadBundle\Segment\Decorator\Date\Week\DateWeekNext;
use Mautic\LeadBundle\Segment\Decorator\Date\Week\DateWeekThis;
use Mautic\LeadBundle\Segment\Decorator\Date\Year\DateYearLast;
use Mautic\LeadBundle\Segment\Decorator\Date\Year\DateYearNext;
use Mautic\LeadBundle\Segment\Decorator\Date\Year\DateYearThis;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\RelativeDate;

class DateOptionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testBirthday(): void
    {
        $filterName = 'birthday';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateAnniversary::class, $filterDecorator);

        $filterName = 'anniversary';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateAnniversary::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testDayToday(): void
    {
        $filterName = 'today';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateDayToday::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testDayTomorrow(): void
    {
        $filterName = 'tomorrow';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateDayTomorrow::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testDayYesterday(): void
    {
        $filterName = 'yesterday';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateDayYesterday::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testWeekLast(): void
    {
        $filterName = 'last week';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateWeekLast::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testWeekNext(): void
    {
        $filterName = 'next week';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateWeekNext::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testWeekThis(): void
    {
        $filterName = 'this week';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateWeekThis::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testMonthLast(): void
    {
        $filterName = 'last month';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateMonthLast::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testMonthNext(): void
    {
        $filterName = 'next month';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateMonthNext::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testMonthThis(): void
    {
        $filterName = 'this month';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateMonthThis::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testYearLast(): void
    {
        $filterName = 'last year';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateYearLast::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testYearNext(): void
    {
        $filterName = 'next year';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateYearNext::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testYearThis(): void
    {
        $filterName = 'this year';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateYearThis::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testRelativePlus(): void
    {
        $filterName = '+20 days';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateRelativeInterval::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testRelativeMinus(): void
    {
        $filterName = '+20 days';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateRelativeInterval::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testRelativeAgo(): void
    {
        $filterName = '20 days ago';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateRelativeInterval::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testRelativeFirstDayOf(): void
    {
        $filterName = 'first day of previous month';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateRelativeInterval::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testRelativeLastDayOf(): void
    {
        $filterName = 'last day of previous month';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateRelativeInterval::class, $filterDecorator);
    }

    /**
     * @return string[][]
     */
    public function getRelativeDateNotations(): array
    {
        return [
            [DateRelativeInterval::class, 'first day of January 2021'],
            [DateRelativeInterval::class, 'last day of January 2021'],
            [DateRelativeInterval::class, '5 days ago'],
            [DateDefault::class, 'day of January 2021'],
        ];
    }

    /**
     * @dataProvider getRelativeDateNotations
     */
    public function testRelativeDateNotations(string $expectedResult, string $filterName): void
    {
        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf($expectedResult, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testDateDefault(): void
    {
        $filterName = '2018-01-01';

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateDefault::class, $filterDecorator);
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::getDateOption
     */
    public function testNullValue(): void
    {
        $filterName = null;

        $filterDecorator = $this->getFilterDecorator($filterName);

        $this->assertInstanceOf(DateDefault::class, $filterDecorator);
    }

    /**
     * @param string $filterName
     *
     * @return \Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface
     */
    private function getFilterDecorator($filterName)
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $relativeDate     = $this->createMock(RelativeDate::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);

        $relativeDate->method('getRelativeDateStrings')
            ->willReturn(
                [
                    'mautic.lead.list.month_last'  => 'last month',
                    'mautic.lead.list.month_next'  => 'next month',
                    'mautic.lead.list.month_this'  => 'this month',
                    'mautic.lead.list.today'       => 'today',
                    'mautic.lead.list.tomorrow'    => 'tomorrow',
                    'mautic.lead.list.yesterday'   => 'yesterday',
                    'mautic.lead.list.week_last'   => 'last week',
                    'mautic.lead.list.week_next'   => 'next week',
                    'mautic.lead.list.week_this'   => 'this week',
                    'mautic.lead.list.year_last'   => 'last year',
                    'mautic.lead.list.year_next'   => 'next year',
                    'mautic.lead.list.year_this'   => 'this year',
                    'mautic.lead.list.birthday'    => 'birthday',
                    'mautic.lead.list.anniversary' => 'anniversary',
                ]
            );

        $dateOptionFactory = new DateOptionFactory($dateDecorator, $relativeDate, $timezoneResolver);

        $filter                    = [
            'glue'     => 'and',
            'type'     => 'datetime',
            'object'   => 'lead',
            'field'    => 'date_identified',
            'operator' => '=',
            'filter'   => $filterName,
            'display'  => null,
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        return $dateOptionFactory->getDateOption($contactSegmentFilterCrate);
    }
}
