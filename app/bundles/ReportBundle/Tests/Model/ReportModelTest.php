<?php

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\Model\CsvExporter;
use Mautic\ReportBundle\Model\ExcelExporter;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Tests\Fixtures;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\Translator;

class ReportModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ReportModel
     */
    private $reportModel;

    protected function setUp(): void
    {
        $fieldModelMock = $this->createMock(FieldModel::class);
        $fieldModelMock->method('getPublishedFieldArrays')->willReturn([]);

        $this->reportModel = new ReportModel(
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(TemplatingHelper::class),
            $this->createMock(ChannelListHelper::class),
            $fieldModelMock,
            $this->createMock(ReportHelper::class),
            $this->createMock(CsvExporter::class),
            $this->createMock(ExcelExporter::class)
        );

        $mockDispatcher = $this->createMock(EventDispatcher::class);
        $mockDispatcher->method('dispatch')
            ->willReturnCallback(
                function ($eventName, ReportBuilderEvent $event) {
                    $reportBuilderData = Fixtures::getReportBuilderEventData();
                    $event->addTable('assets', $reportBuilderData['all']['tables']['assets']);
                }
            );
        $this->reportModel->setDispatcher($mockDispatcher);

        $translatorMock = $this->createMock(Translator::class);
        // Make the translator return whatever string is passed to it instead of null
        $translatorMock->method('trans')->withAnyParameters()->willReturnArgument(0);
        $this->reportModel->setTranslator($translatorMock);

        // Do this to build the initial set of data from the subscribers that get used in all other contexts
        $this->reportModel->buildAvailableReports('all');

        parent::setUp();
    }

    public function testGetColumnListWithContext()
    {
        $properContextFormat = 'assets';
        $actual              = $this->reportModel->getColumnList($properContextFormat);
        $expected            = Fixtures::getGoodColumnList();

        $this->assertEquals($expected->choices, $actual->choices);
        $this->assertEquals($expected->definitions, $actual->definitions);
    }
}
