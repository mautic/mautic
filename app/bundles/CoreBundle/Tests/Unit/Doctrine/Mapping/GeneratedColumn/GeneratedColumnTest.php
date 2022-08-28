<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Mapping\GeneratedColumn;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;

class GeneratedColumnTest extends \PHPUnit\Framework\TestCase
{
    public function testAllGettersAndSeters(): void
    {
        $generatedColumn = new GeneratedColumn('page_hits', 'generated_hit_date', 'DATE', 'CONCAT(YEAR(date_hit), "-", LPAD(MONTH(date_hit), 2, "0"), "-", LPAD(DAY(date_hit), 2, "0"))');
        $generatedColumn->addIndexColumn('page_id');
        $generatedColumn->setOriginalDateColumn('date_hit', 'd');

        $expectedColumnDefinition = "DATE AS (CONCAT(YEAR(date_hit), \"-\", LPAD(MONTH(date_hit), 2, \"0\"), \"-\", LPAD(DAY(date_hit), 2, \"0\"))) COMMENT '(DC2Type:generated)'";
        $expectedAlterQuery       = 'ALTER TABLE '.MAUTIC_TABLE_PREFIX."page_hits ADD generated_hit_date DATE AS (CONCAT(YEAR(date_hit), \"-\", LPAD(MONTH(date_hit), 2, \"0\"), \"-\", LPAD(DAY(date_hit), 2, \"0\"))) COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'page_hits ADD INDEX `'.MAUTIC_TABLE_PREFIX.'generated_hit_date_page_id`(generated_hit_date, page_id)';

        $this->assertSame($expectedAlterQuery, $generatedColumn->getAlterTableSql());
        $this->assertSame($expectedColumnDefinition, $generatedColumn->getColumnDefinition());
        $this->assertSame('generated_hit_date', $generatedColumn->getColumnName());
        $this->assertSame(['generated_hit_date', 'page_id'], $generatedColumn->getIndexColumns());
        $this->assertSame(MAUTIC_TABLE_PREFIX.'generated_hit_date_page_id', $generatedColumn->getIndexName());
        $this->assertSame('date_hit', $generatedColumn->getOriginalDateColumn());
        $this->assertSame(MAUTIC_TABLE_PREFIX.'page_hits', $generatedColumn->getTableName());
        $this->assertSame('d', $generatedColumn->getTimeUnit());
    }
}
