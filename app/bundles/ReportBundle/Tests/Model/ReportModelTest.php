<?php

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\Model\CsvExporter;
use Mautic\ReportBundle\Model\ExcelExporter;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Tests\Fixtures;

class ReportModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var
     */
    private $reportModel;

    protected function setUp()
    {
        $this->reportModel = new ReportModel(
            $this->getMockBuilder(CoreParametersHelper::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(TemplatingHelper::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ChannelListHelper::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(FieldModel::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ReportHelper::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(CsvExporter::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ExcelExporter::class)->disableOriginalConstructor()->getMock()
        );
    }

    public function testGetColumnListWithContext()
    {
        $this->reportModel->reportBuilderData = Fixtures::getReportBuilderEventData();

        $actual   = $this->reportModel->getColumnList('assets');
        $expected = Fixtures::getGoodColumnList();

        $this->assertEquals($expected->choices, $actual->choices);
        $this->assertEquals($expected->definitions, $actual->definitions);
    }
}
