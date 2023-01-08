<?php

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

class ContactSegmentFilterCrateTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testMultiselectFilter()
    {
        $filter = [
            'glue'     => 'and',
            'field'    => 'multiselect_cf',
            'object'   => 'lead',
            'type'     => 'multiselect',
            'filter'   => [2, 4],
            'display'  => null,
            'operator' => 'in',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertSame('and', $contactSegmentFilterCrate->getGlue());
        $this->assertSame('multiselect_cf', $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertSame([2, 4], $contactSegmentFilterCrate->getFilter());
        $this->assertSame('multiselect', $contactSegmentFilterCrate->getOperator());
        $this->assertFalse($contactSegmentFilterCrate->isBooleanType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testNotMultiselectFilter()
    {
        $filter = [
            'glue'     => 'and',
            'field'    => 'multiselect_cf',
            'object'   => 'lead',
            'type'     => 'multiselect',
            'filter'   => [2, 4],
            'display'  => null,
            'operator' => '!in',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertSame('and', $contactSegmentFilterCrate->getGlue());
        $this->assertSame('multiselect_cf', $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertSame([2, 4], $contactSegmentFilterCrate->getFilter());
        $this->assertSame('!multiselect', $contactSegmentFilterCrate->getOperator());
        $this->assertFalse($contactSegmentFilterCrate->isBooleanType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testOldEqualInsteadOfInOperator()
    {
        $filter = [
            'glue'     => 'and',
            'field'    => 'tags',
            'object'   => 'lead',
            'type'     => 'tags',
            'filter'   => [3],
            'display'  => null,
            'operator' => '=',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertSame('and', $contactSegmentFilterCrate->getGlue());
        $this->assertSame('tags', $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertSame([3], $contactSegmentFilterCrate->getFilter());
        $this->assertSame('in', $contactSegmentFilterCrate->getOperator());
        $this->assertFalse($contactSegmentFilterCrate->isBooleanType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     *
     * @dataProvider specialFieldsToConvertToEmptyProvider
     */
    public function testSpecialFieldsToConvertToNotEmpty($field)
    {
        $filter = [
            'glue'     => 'and',
            'field'    => $field,
            'object'   => 'lead',
            'type'     => 'boolean',
            'filter'   => 1,
            'display'  => null,
            'operator' => '=',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertSame('and', $contactSegmentFilterCrate->getGlue());
        $this->assertSame($field, $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertTrue($contactSegmentFilterCrate->getFilter());
        $this->assertSame('notEmpty', $contactSegmentFilterCrate->getOperator());
        $this->assertTrue($contactSegmentFilterCrate->isBooleanType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     *
     * @dataProvider specialFieldsToConvertToEmptyProvider
     */
    public function testSpecialFieldsToConvertToEmpty($field)
    {
        $filter = [
            'glue'     => 'and',
            'field'    => $field,
            'object'   => 'lead',
            'type'     => 'boolean',
            'filter'   => 0,
            'display'  => null,
            'operator' => '=',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertSame('and', $contactSegmentFilterCrate->getGlue());
        $this->assertSame($field, $contactSegmentFilterCrate->getField());
        $this->assertTrue($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertFalse($contactSegmentFilterCrate->getFilter());
        $this->assertSame('empty', $contactSegmentFilterCrate->getOperator());
        $this->assertTrue($contactSegmentFilterCrate->isBooleanType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
    }

    public function specialFieldsToConvertToEmptyProvider()
    {
        return [
            ['page_id'],
            ['email_id'],
            ['redirect_id'],
            ['notification'],
        ];
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterCrate
     */
    public function testBehaviorsTypeFilter(): void
    {
        $filter = [
            'object'     => 'behaviors',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertFalse($contactSegmentFilterCrate->isContactType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyType());
        $this->assertTrue($contactSegmentFilterCrate->isBehaviorsType());
    }
}
