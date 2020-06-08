<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Doctrine\GeneratedColumn;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;

class GeneratedColumnTest extends \PHPUnit\Framework\TestCase
{
    public function testAllGettersAndSeters()
    {
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $generatedColumn = new GeneratedColumn('page_hits', 'generated_hit_date', 'DATE', 'CONCAT(YEAR(date_hit), "-", LPAD(MONTH(date_hit), 2, "0"), "-", LPAD(DAY(date_hit), 2, "0"))');
        $generatedColumn->addIndexColumn('page_id');
        $generatedColumn->setOriginalDateColumn('date_hit', 'd');

        $expectedColumnDefinition = "DATE AS (CONCAT(YEAR(date_hit), \"-\", LPAD(MONTH(date_hit), 2, \"0\"), \"-\", LPAD(DAY(date_hit), 2, \"0\"))) COMMENT '(DC2Type:generated)'";
        $expectedAlterQuery       = "ALTER TABLE page_hits ADD generated_hit_date DATE AS (CONCAT(YEAR(date_hit), \"-\", LPAD(MONTH(date_hit), 2, \"0\"), \"-\", LPAD(DAY(date_hit), 2, \"0\"))) COMMENT '(DC2Type:generated)';
            ALTER TABLE page_hits ADD INDEX `generated_hit_date_page_id`(generated_hit_date, page_id)";

        $this->assertSame($expectedAlterQuery, $generatedColumn->getAlterTableSql());
        $this->assertSame($expectedColumnDefinition, $generatedColumn->getColumnDefinition());
        $this->assertSame('generated_hit_date', $generatedColumn->getColumnName());
        $this->assertSame(['generated_hit_date', 'page_id'], $generatedColumn->getIndexColumns());
        $this->assertSame('generated_hit_date_page_id', $generatedColumn->getIndexName());
        $this->assertSame('date_hit', $generatedColumn->getOriginalDateColumn());
        $this->assertSame('page_hits', $generatedColumn->getTableName());
        $this->assertSame('d', $generatedColumn->getTimeUnit());
    }
}
