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
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;

class GeneratedColumnsTest extends \PHPUnit_Framework_TestCase
{
    public function testAllGettersAndSeters()
    {
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

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
