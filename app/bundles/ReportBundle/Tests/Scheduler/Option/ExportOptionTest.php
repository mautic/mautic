<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Option;

use Mautic\ReportBundle\Scheduler\Option\ExportOption;

class ExportOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testReportId()
    {
        $exportOption = new ExportOption(11);

        $this->assertSame(11, $exportOption->getReportId());
    }

    public function testNoReportId()
    {
        $exportOption = new ExportOption(null);

        $this->assertSame(0, $exportOption->getReportId());
    }

    public function testBadFormatOfId()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ExportOption('string');
    }
}
