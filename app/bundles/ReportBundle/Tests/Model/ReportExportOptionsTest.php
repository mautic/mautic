<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\ReportBundle\Model\ReportExportOptions;

class ReportExportOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testBatch()
    {
        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_batch_size')
            ->willReturn(3);

        $reportExportOptions = new ReportExportOptions($coreParametersHelper);

        $this->assertSame(1, $reportExportOptions->getPage());
        $this->assertSame(3, $reportExportOptions->getBatchSize());

        $reportExportOptions->beginExport();
        $this->assertSame(1, $reportExportOptions->getPage());
        $this->assertSame(3, $reportExportOptions->getNumberOfProcessedResults());

        $reportExportOptions->nextBatch();
        $this->assertSame(2, $reportExportOptions->getPage());
        $this->assertSame(6, $reportExportOptions->getNumberOfProcessedResults());

        $reportExportOptions->nextBatch();
        $this->assertSame(3, $reportExportOptions->getPage());
        $this->assertSame(9, $reportExportOptions->getNumberOfProcessedResults());
    }
}
