<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Mapping\GeneratedColumn;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class GeneratedColumnsTest extends TestCase
{
    private GeneratedColumns $generatedColumns;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generatedColumns = new GeneratedColumns();
    }

    public function testIterator(): void
    {
        $columns = [
            new GeneratedColumn('page_hits', 'generated_hit_date', 'DATE', 'not important'),
            new GeneratedColumn('page_hits2', 'generated_hit_date2', 'DATE', 'not important'),
        ];

        foreach ($columns as $column) {
            $this->generatedColumns->add($column);
        }

        Assert::assertCount(count($columns), $this->generatedColumns);

        foreach ($this->generatedColumns as $index => $column) {
            Assert::assertSame($columns[$index], $column);
        }
    }

    /**
     * @deprecated This test is for a deprecated method and will be removed when the method is removed
     */
    public function testGetForOriginalDateColumnAndUnitDoesNotRespectTableName(): void
    {
        $generatedColumn1 = new GeneratedColumn('page_hits', 'generated_added_date', 'DATE', 'not important');
        $generatedColumn1->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($generatedColumn1);

        $generatedColumn2 = new GeneratedColumn('downloads', 'generated_added_date', 'DATE', 'not important');
        $generatedColumn2->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($generatedColumn2);

        // We need to test the deprecated method's functionality
        // Intentionally using deprecated method for testing backwards compatibility
        $this->assertSame($generatedColumn2, $this->generatedColumns->getForOriginalDateColumnAndUnit('date_added', 'd'));
    }

    /**
     * @deprecated This test is for a deprecated method and will be removed when the method is removed
     *
     * @dataProvider dataGetForOriginalDateColumnAndUnitUnexpectedValue
     */
    public function testGetForOriginalDateColumnAndUnitUnexpectedValueIsThrown(string $column, string $unit): void
    {
        $generatedColumn = new GeneratedColumn('page_hits', 'generated_added_date', 'DATE', 'not important');
        $generatedColumn->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($generatedColumn);

        $this->expectException(\UnexpectedValueException::class);
        // Intentionally using deprecated method for testing backwards compatibility
        $this->generatedColumns->getForOriginalDateColumnAndUnit($column, $unit);
    }

    /**
     * @deprecated This data provider is for a deprecated method test
     *
     * @return iterable<array<string>>
     */
    public function dataGetForOriginalDateColumnAndUnitUnexpectedValue(): iterable
    {
        yield ['date_added', 'Y'];
        yield ['date_updated', 'd'];
        yield ['non-existent', 'i'];
    }

    /**
     * Testing that the getGeneratedColumnForDateColumn method properly uses table names
     * to find the correct generated column.
     */
    public function testGetGeneratedColumnForDateColumnRespectsTableName(): void
    {
        // Create and add two generated columns for different tables
        $column1 = new GeneratedColumn('page_hits', 'generated_added_date', 'DATE', 'not important');
        $column1->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($column1);

        $column2 = new GeneratedColumn('downloads', 'generated_added_date', 'DATE', 'not important');
        $column2->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($column2);

        $actualTableName1 = $column1->getTableName();
        $actualTableName2 = $column2->getTableName();

        // Test using the actual table names including prefix
        $this->assertSame($column1, $this->generatedColumns->getGeneratedColumnForDateColumn($actualTableName1, 'date_added', 'd'));
        $this->assertSame($column2, $this->generatedColumns->getGeneratedColumnForDateColumn($actualTableName2, 'date_added', 'd'));
    }

    /**
     * @dataProvider dataGetGeneratedColumnForDateColumnUnexpectedValue
     */
    public function testGetGeneratedColumnForDateColumnUnexpectedValueIsThrown(string $table, string $column, string $unit): void
    {
        $generatedColumn = new GeneratedColumn('page_hits', 'generated_added_date', 'DATE', 'not important');
        $generatedColumn->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($generatedColumn);

        $this->expectException(\UnexpectedValueException::class);
        $this->generatedColumns->getGeneratedColumnForDateColumn($table, $column, $unit);
    }

    /**
     * @return iterable<array<string>>
     */
    public function dataGetGeneratedColumnForDateColumnUnexpectedValue(): iterable
    {
        yield ['page_hits', 'date_added', 'Y'];
        yield ['page_hits', 'date_updated', 'd'];
        yield ['non-existent', 'date_added', 'd'];
        yield ['non-existent', 'non-existent', 'i'];
    }
}
