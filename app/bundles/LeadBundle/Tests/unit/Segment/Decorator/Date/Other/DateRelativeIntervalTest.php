<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Other;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

class DateRelativeIntervalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getOperator
     */
    public function testGetOperatorEqual()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);
        $filter        = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '+5 days');

        $this->assertEquals('like', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getOperator
     */
    public function testGetOperatorNotEqual()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);
        $filter        = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '+5 days');

        $this->assertEquals('notLike', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getOperator
     */
    public function testGetOperatorLessOrEqual()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $dateDecorator->method('getOperator')
            ->with()
            ->willReturn('==<<'); //Test that value is really returned from Decorator

        $filter        = [
            'operator' => '=<',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '+5 days');

        $this->assertEquals('==<<', $filterDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValuePlusDaysWithGreaterOperator()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '>',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '+5 days');

        $this->assertEquals('2018-03-07', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueMinusMonthWithNotEqualOperator()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '-3 months');

        $this->assertEquals('2017-12-02%', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueDaysAgoWithNotEqualOperator()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '!=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '5 days ago');

        $this->assertEquals('2018-02-25%', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueYearsAgoWithGreaterOperator()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '>',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '2 years ago');

        $this->assertEquals('2016-03-02', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateRelativeInterval::getParameterValue
     */
    public function testGetParameterValueDaysWithEqualOperator()
    {
        $dateDecorator = $this->createMock(DateDecorator::class);

        $date = new DateTimeHelper('2018-03-02', null, 'local');

        $dateDecorator->method('getDefaultDate')
            ->with()
            ->willReturn($date);

        $filter = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $filterDecorator = new DateRelativeInterval($dateDecorator, '5 days');

        $this->assertEquals('2018-03-07%', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }
}
