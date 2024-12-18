<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\TableSchemaColumnsCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContactSegmentFilterTest extends TestCase
{
    private ContactSegmentFilterCrate $contactSegmentFilterCrate;

    /**
     * @var FilterDecoratorInterface&MockObject
     */
    private MockObject $filterDecorator;

    /**
     * @var TableSchemaColumnsCache|MockObject
     */
    private MockObject $tableSchemaColumnCache;

    /**
     * @var FilterQueryBuilderInterface&MockObject
     */
    private MockObject $filterQueryBuilder;

    protected function setUp(): void
    {
        $this->contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);
        $this->filterDecorator           = $this->createMock(BaseDecorator::class);
        $this->tableSchemaColumnCache    = $this->createMock(TableSchemaColumnsCache::class);
        $this->filterQueryBuilder        = $this->createMock(FilterQueryBuilderInterface::class);

        parent::setUp();
    }

    public function testGetType(): void
    {
        $type                            = 'type';
        $this->contactSegmentFilterCrate = new ContactSegmentFilterCrate(['type' => $type]);
        $filter                          = $this->createContactSegmentFilter();

        self::assertEquals($type, $filter->getType());
    }

    public function testGetParameterValue(): void
    {
        $value = 'value';

        $this->filterDecorator->expects(self::once())
            ->method('getParameterValue')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($value);

        $filter = $this->createContactSegmentFilter();

        self::assertEquals($value, $filter->getParameterValue());
    }

    public function testGetTable(): void
    {
        $table = 'table';

        $this->filterDecorator->expects(self::once())
            ->method('getTable')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($table);

        $filter = $this->createContactSegmentFilter();

        self::assertEquals($table, $filter->getTable());
    }

    public function testIsColumnTypeBoolean(): void
    {
        $this->contactSegmentFilterCrate = new ContactSegmentFilterCrate(['type' => 'boolean']);
        $filter                          = $this->createContactSegmentFilter();

        self::assertTrue($filter->isColumnTypeBoolean());

        $this->contactSegmentFilterCrate = new ContactSegmentFilterCrate(['type' => 'something']);
        $filter                          = $this->createContactSegmentFilter();

        self::assertFalse($filter->isColumnTypeBoolean());
    }

    public function testGetFilterQueryBuilder(): void
    {
        $filter = $this->createContactSegmentFilter();

        $this->assertEquals($this->filterQueryBuilder, $filter->getFilterQueryBuilder());
    }

    public function testGetDoNotContactParts(): void
    {
        $filter = $this->createContactSegmentFilter();

        $parts = $filter->getDoNotContactParts();

        self::assertEquals('email', $parts->getChannel());
        self::assertEquals(1, $parts->getParameterType());
    }

    public function testGetParameterHolder(): void
    {
        $argument       = 'argument';
        $expectedResult = 'expectedResult';

        $this->filterDecorator->expects(self::once())
            ->method('getParameterHolder')
            ->with($this->contactSegmentFilterCrate, $argument)
            ->willReturn($expectedResult);

        $filter = $this->createContactSegmentFilter();

        self::assertEquals($expectedResult, $filter->getParameterHolder($argument));
    }

    public function testGetWhere(): void
    {
        $where = 'where';

        $filter = $this->createContactSegmentFilter();

        $this->filterDecorator->expects(self::once())
            ->method('getWhere')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($where);

        self::assertEquals($where, $filter->getWhere());
    }

    public function testIsContactSegmentReference(): void
    {
        $filter = $this->createContactSegmentFilter();

        $this->filterDecorator->method('getField')
            ->withConsecutive(
                [$this->contactSegmentFilterCrate],
                [$this->contactSegmentFilterCrate]
            )
            ->willReturnOnConsecutiveCalls('leadlist', 'something');

        self::assertTrue($filter->isContactSegmentReference());
        self::assertFalse($filter->isContactSegmentReference());
    }

    public function testGetGlue(): void
    {
        $glue = 'glue';

        $this->contactSegmentFilterCrate = new ContactSegmentFilterCrate(['glue' => $glue]);
        $filter                          = $this->createContactSegmentFilter();

        self::assertSame($glue, $filter->getGlue());
    }

    public function testGetIntegrationCampaignParts(): void
    {
        $value = 'value';

        $filter = $this->createContactSegmentFilter();

        $this->filterDecorator->expects(self::once())
            ->method('getParameterValue')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($value);

        $parts = $filter->getIntegrationCampaignParts();

        self::assertEquals($value, $parts->getCampaignId());
    }

    public function testApplyQuery(): void
    {
        $queryBuilder = new QueryBuilder($this->createMock(\Doctrine\DBAL\Connection::class));

        $this->filterQueryBuilder->expects(self::once())
            ->method('applyQuery')
            ->willReturn($queryBuilder);

        $filter = $this->createContactSegmentFilter();

        self::assertSame($queryBuilder, $filter->applyQuery($queryBuilder));
    }

    public function testGetRelationJoinTable(): void
    {
        $table = 'table';

        $filter = $this->createContactSegmentFilter();

        self::assertNull($filter->getRelationJoinTable());

        $this->filterDecorator = $this->getMockBuilder(FilterDecoratorInterface::class)
            ->addMethods(['getRelationJoinTable'])
            ->getMockForAbstractClass();
        $this->filterDecorator->expects(self::once())
            ->method('getRelationJoinTable')
            ->willReturn($table);

        $filter = $this->createContactSegmentFilter();

        self::assertEquals($table, $filter->getRelationJoinTable());
    }

    public function testGetQueryType(): void
    {
        $type = 'type';

        $filter = $this->createContactSegmentFilter();

        $this->filterDecorator->expects(self::once())
            ->method('getQueryType')
            ->willReturn($type);

        self::assertSame($type, $filter->getQueryType());
    }

    public function testGetNullValue(): void
    {
        $value = 'value';

        $this->contactSegmentFilterCrate = new ContactSegmentFilterCrate(['null_value' => $value]);

        $filter = $this->createContactSegmentFilter();

        self::assertSame($value, $filter->getNullValue());
    }

    public function testGetColumnMissingColumn(): void
    {
        $dbName    = 'dbName';
        $tableName = 'tableName';
        $columns   = ['column1', 'column2'];

        $this->tableSchemaColumnCache->expects(self::once())
            ->method('getCurrentDatabaseName')
            ->willReturn($dbName);

        $this->filterDecorator->expects(self::exactly(2))
            ->method('getTable')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($tableName);

        $this->tableSchemaColumnCache->expects(self::once())
            ->method('getColumns')
            ->with($tableName)
            ->willReturn($columns);

        $this->filterDecorator->expects(self::exactly(2))
            ->method('getField')
            ->willReturn('notExistingColumn');

        $this->expectException(FieldNotFoundException::class);
        $filter = $this->createContactSegmentFilter();
        $filter->getColumn();
    }

    public function testGetColumn(): void
    {
        $dbName    = 'dbName';
        $tableName = 'tableName';
        $columns   = ['column1' => 'something1', 'column2' => 'something2'];

        $this->tableSchemaColumnCache->expects(self::once())
            ->method('getCurrentDatabaseName')
            ->willReturn($dbName);

        $this->filterDecorator->expects(self::once())
            ->method('getTable')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($tableName);

        $this->tableSchemaColumnCache->expects(self::once())
            ->method('getColumns')
            ->with($tableName)
            ->willReturn($columns);

        $this->filterDecorator->expects(self::exactly(2))
            ->method('getField')
            ->willReturn('column1');

        $filter = $this->createContactSegmentFilter();
        $this->assertEquals('something1', $filter->getColumn());
    }

    public function testGetField(): void
    {
        $field = 'field';

        $this->filterDecorator->expects(self::once())
            ->method('getField')
            ->willReturn($field);

        $filter = $this->createContactSegmentFilter();

        self::assertSame($field, $filter->getField());
    }

    public function testGetRelationJoinTableField(): void
    {
        $field = 'field';

        $filter = $this->createContactSegmentFilter();

        self::assertNull($filter->getRelationJoinTableField());

        $this->filterDecorator = $this->getMockBuilder(FilterDecoratorInterface::class)
            ->addMethods(['getRelationJoinTableField'])
            ->getMockForAbstractClass();
        $this->filterDecorator->expects(self::once())
            ->method('getRelationJoinTableField')
            ->willReturn($field);

        $filter = $this->createContactSegmentFilter();

        self::assertEquals($field, $filter->getRelationJoinTableField());
    }

    public function testGetAggregateFunction(): void
    {
        $function = 'function';

        $filter = $this->createContactSegmentFilter();

        $this->filterDecorator->expects(self::once())
            ->method('getAggregateFunc')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($function);

        self::assertSame($function, $filter->getAggregateFunction());
    }

    public function testGetOperator(): void
    {
        $operator = 'operator';

        $filter = $this->createContactSegmentFilter();

        $this->filterDecorator->expects(self::once())
            ->method('getOperator')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($operator);

        self::assertSame($operator, $filter->getOperator());
    }

    public function testToString(): void
    {
        $table          = 'table';
        $field          = 'field';
        $queryType      = 'queryType';
        $operator       = 'operator';
        $parameterValue = ['parameterValue'];

        $expectedResult = sprintf(
            'table: %s,  %s on %s %s %s',
            $table,
            $field,
            $queryType,
            $operator,
            json_encode($parameterValue)
        );

        $this->filterDecorator->expects(self::once())
            ->method('getTable')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($table);

        $this->filterDecorator->expects(self::once())
            ->method('getField')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($field);

        $this->filterDecorator->expects(self::once())
            ->method('getQueryType')
            ->willReturn($queryType);

        $this->filterDecorator->expects(self::once())
            ->method('getOperator')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($operator);

        $this->filterDecorator->expects(self::once())
            ->method('getParameterValue')
            ->with($this->contactSegmentFilterCrate)
            ->willReturn($parameterValue);

        $filter = $this->createContactSegmentFilter();

        $result = $filter->__toString();
        self::assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider dataDoesColumnSupportEmptyValue
     */
    public function testDoesColumnSupportEmptyValue(string $type, bool $doesColumnSupportEmptyValue): void
    {
        $this->contactSegmentFilterCrate = new ContactSegmentFilterCrate(['type' => $type]);
        $filter                          = $this->createContactSegmentFilter();

        self::assertEquals($doesColumnSupportEmptyValue, $filter->doesColumnSupportEmptyValue());
    }

    public function testBatchLimitersAreSetCorrectly(): void
    {
        $filter = new ContactSegmentFilter(
            $this->contactSegmentFilterCrate,
            $this->filterDecorator,
            $this->tableSchemaColumnCache,
            $this->filterQueryBuilder,
            [
                'minId' => 1,
                'maxId' => 1,
            ]
        );
        self::assertSame([
            'minId' => 1,
            'maxId' => 1,
        ], $filter->getBatchLimiters());
    }

    /**
     * @return iterable<array<bool|string>>
     */
    public function dataDoesColumnSupportEmptyValue(): iterable
    {
        yield ['boolean', true];
        yield ['date', false];
        yield ['datetime', false];
        yield ['email', true];
        yield ['html', true];
        yield ['country', true];
        yield ['locale', true];
        yield ['lookup', true];
        yield ['number', true];
        yield ['tel', true];
        yield ['region', true];
        yield ['select', true];
        yield ['multiselect', true];
        yield ['text', true];
        yield ['textarea', true];
        yield ['time', true];
        yield ['timezone', true];
        yield ['url', true];
    }

    private function createContactSegmentFilter(): ContactSegmentFilter
    {
        return new ContactSegmentFilter(
            $this->contactSegmentFilterCrate,
            $this->filterDecorator,
            $this->tableSchemaColumnCache,
            $this->filterQueryBuilder
        );
    }
}
