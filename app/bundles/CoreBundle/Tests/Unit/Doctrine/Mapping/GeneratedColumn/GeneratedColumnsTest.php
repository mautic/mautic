<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\GeneratedColumn;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GeneratedColumnsTest extends TestCase
{
    private GeneratedColumns $generatedColumns;

    protected function setUp(): void
    {
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

        $this->assertCount(count($columns), $this->generatedColumns);

        foreach ($this->generatedColumns as $index => $column) {
            $this->assertSame($columns[$index], $column);
        }
    }

    public function testGetGeneratedColumnForDateColumnRespectsTableName(): void
    {
        $generatedColumn1 = new GeneratedColumn('page_hits', 'generated_added_date', 'DATE', 'not important');
        $generatedColumn1->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($generatedColumn1);

        $generatedColumn2 = new GeneratedColumn('downloads', 'generated_added_date', 'DATE', 'not important');
        $generatedColumn2->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($generatedColumn2);

        $this->assertSame($generatedColumn1, $this->generatedColumns->getGeneratedColumnForDateColumn(MAUTIC_TABLE_PREFIX.'page_hits', 'date_added', 'd'));
        $this->assertSame($generatedColumn2, $this->generatedColumns->getGeneratedColumnForDateColumn(MAUTIC_TABLE_PREFIX.'downloads', 'date_added', 'd'));
    }

    #[DataProvider('dataGetGeneratedColumnForDateColumnUnexpectedValue')]
    public function testGetGeneratedColumnForDateColumnUnexpectedValueIsThrown(string $table, string $column, string $unit): void
    {
        $generatedColumn = new GeneratedColumn('page_hits', 'generated_added_date', 'DATE', 'not important');
        $generatedColumn->setOriginalDateColumn('date_added', 'd');
        $this->generatedColumns->add($generatedColumn);

        $this->expectException(\UnexpectedValueException::class);
        $this->generatedColumns->getGeneratedColumnForDateColumn($table, $column, $unit);
    }

    /**
     * @return iterable<string[]>
     */
    public static function dataGetGeneratedColumnForDateColumnUnexpectedValue(): iterable
    {
        yield ['page_hits', 'date_added', 'Y'];
        yield ['page_hits', 'date_updated', 'd'];
        yield ['non-existent', 'date_added', 'd'];
        yield ['non-existent', 'non-existent', 'i'];
    }
}
