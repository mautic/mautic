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
        $this->assertNull($contactSegmentFilterCrate->getType());
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

        $this->assertEquals('and', $contactSegmentFilterCrate->getGlue());
        $this->assertEquals('date_identified', $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertEquals('datetime', $contactSegmentFilterCrate->getType());
        $this->assertNull($contactSegmentFilterCrate->getFilter());
        $this->assertEquals('!empty', $contactSegmentFilterCrate->getOperator());
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

        $this->assertEquals('and', $contactSegmentFilterCrate->getGlue());
        $this->assertEquals('date_identified', $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertEquals('date', $contactSegmentFilterCrate->getType());
        $this->assertNull($contactSegmentFilterCrate->getFilter());
        $this->assertEquals('!empty', $contactSegmentFilterCrate->getOperator());
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
            'type' => 'boolean',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertEquals('boolean', $contactSegmentFilterCrate->getType());
        $this->assertTrue($contactSegmentFilterCrate->isBooleanType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
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
