<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Mapping\GeneratedColumn;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;

class GeneratedColumnsTest extends \PHPUnit\Framework\TestCase
{
    public function testAllGettersAndSeters(): void
    {
        $generatedColumn1 = new GeneratedColumn('page_hits', 'generated_hit_date', 'DATE', 'not important');
        $generatedColumn2 = new GeneratedColumn('page_hits2', 'generated_hit_date2', 'DATE', 'not important');

        $generatedColumn2->setOriginalDateColumn('date_hit', 'd');

        $generatedColumns = new GeneratedColumns();

        $generatedColumns->add($generatedColumn1);
        $generatedColumns->add($generatedColumn2);

        $this->assertCount(2, $generatedColumns);
        $this->assertSame($generatedColumn2, $generatedColumns->getForOriginalDateColumnAndUnit('date_hit', 'd'));

        $this->expectException(\UnexpectedValueException::class);
        $generatedColumns->getForOriginalDateColumnAndUnit('not-found', 'd');
    }
}
