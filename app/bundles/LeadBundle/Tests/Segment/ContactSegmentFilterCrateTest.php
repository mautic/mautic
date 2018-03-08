<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

class ContactSegmentFilterCrateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testEmptyFilter()
    {
        $filter = [];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertNull($contactSegmentFilterCrate->getGlue());
        $this->assertNull($contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertNull($contactSegmentFilterCrate->getFilter());
        $this->assertNull($contactSegmentFilterCrate->getOperator());
        $this->assertFalse($contactSegmentFilterCrate->isBooleanType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testDateIdentifiedFilter()
    {
        $filter = [
            'glue'     => 'and',
            'field'    => 'date_identified',
            'object'   => 'lead',
            'type'     => 'datetime',
            'filter'   => null,
            'display'  => null,
            'operator' => '!empty',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertSame('and', $contactSegmentFilterCrate->getGlue());
        $this->assertSame('date_identified', $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertNull($contactSegmentFilterCrate->getFilter());
        $this->assertSame('!empty', $contactSegmentFilterCrate->getOperator());
        $this->assertFalse($contactSegmentFilterCrate->isBooleanType());
        $this->assertTrue($contactSegmentFilterCrate->isDateType());
        $this->assertTrue($contactSegmentFilterCrate->hasTimeParts());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testDateFilter()
    {
        $filter = [
            'glue'     => 'and',
            'field'    => 'date_identified',
            'object'   => 'lead',
            'type'     => 'date',
            'filter'   => null,
            'display'  => null,
            'operator' => '!empty',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertSame('and', $contactSegmentFilterCrate->getGlue());
        $this->assertSame('date_identified', $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertNull($contactSegmentFilterCrate->getFilter());
        $this->assertSame('!empty', $contactSegmentFilterCrate->getOperator());
        $this->assertFalse($contactSegmentFilterCrate->isBooleanType());
        $this->assertTrue($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testBooleanFilter()
    {
        $filter = [
            'type'   => 'boolean',
            'filter' => '1',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertTrue($contactSegmentFilterCrate->getFilter());
        $this->assertTrue($contactSegmentFilterCrate->isBooleanType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
        $this->assertTrue($contactSegmentFilterCrate->filterValueDoNotNeedAdjustment());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testNumericFilter()
    {
        $filter = [
            'type'   => 'number',
            'filter' => '2',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertSame(2.0, $contactSegmentFilterCrate->getFilter());
        $this->assertTrue($contactSegmentFilterCrate->isNumberType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
        $this->assertTrue($contactSegmentFilterCrate->filterValueDoNotNeedAdjustment());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testCompanyTypeFilter()
    {
        $filter = [
            'object' => 'company',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertFalse($contactSegmentFilterCrate->isContactType());
        $this->assertTrue($contactSegmentFilterCrate->isCompanyType());
    }
}
