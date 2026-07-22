<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ContactSegmentFilterCrate::class)]
final class ContactSegmentFilterCrateTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyFilter(): void
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

    public function testDateIdentifiedFilter(): void
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

    public function testDateFilter(): void
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

    public function testBooleanFilter(): void
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

    public function testNumericFilter(): void
    {
        $filter = [
            'type'   => 'number',
            'filter' => '2',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertEqualsWithDelta(2.0, $contactSegmentFilterCrate->getFilter(), PHP_FLOAT_EPSILON);
        $this->assertTrue($contactSegmentFilterCrate->isNumberType());
        $this->assertFalse($contactSegmentFilterCrate->isDateType());
        $this->assertFalse($contactSegmentFilterCrate->hasTimeParts());
        $this->assertTrue($contactSegmentFilterCrate->filterValueDoNotNeedAdjustment());
    }

    public function testCompanyTypeFilter(): void
    {
        $filter = [
            'object' => 'company',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertFalse($contactSegmentFilterCrate->isContactType());
        $this->assertTrue($contactSegmentFilterCrate->isCompanyType());
        $this->assertTrue($contactSegmentFilterCrate->isPrimaryCompanyType());
        $this->assertFalse($contactSegmentFilterCrate->isCompanyAllType());
    }

    public function testCompanyAllTypeFilter(): void
    {
        $filter = [
            'object' => ContactSegmentFilterCrate::COMPANY_ALL_OBJECT,
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $this->assertFalse($contactSegmentFilterCrate->isContactType());
        $this->assertTrue($contactSegmentFilterCrate->isCompanyType());
        $this->assertFalse($contactSegmentFilterCrate->isPrimaryCompanyType());
        $this->assertTrue($contactSegmentFilterCrate->isCompanyAllType());
    }

    public function testMultiselectFilter(): void
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

    public function testNotMultiselectFilter(): void
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

    public function testOldEqualInsteadOfInOperator(): void
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

    #[DataProvider('specialFieldsToConvertToEmptyProvider')]
    public function testSpecialFieldsToConvertToNotEmpty(string $field): void
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

    #[DataProvider('specialFieldsToConvertToEmptyProvider')]
    public function testSpecialFieldsToConvertToEmpty(string $field): void
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

    /**
     * @return \Iterator<int, array{string}>
     */
    public static function specialFieldsToConvertToEmptyProvider(): \Iterator
    {
        yield ['page_id'];
        yield ['email_id'];
        yield ['redirect_id'];
        yield ['notification'];
    }

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
