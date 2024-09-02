<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Model\ExcelExporter;
use Mautic\ReportBundle\Tests\Fixtures;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExcelExporterTest extends TestCase
{
    private ExcelExporter $excelExporter;

    private string|false $tmpFile;

    public function setUp(): void
    {
        $translator       = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->with('mautic.report.report.groupby.totals')
            ->willReturn('Totals');

        $dateHelperMock   =new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $translator,
            $this->createMock(CoreParametersHelper::class)
        );

        $formatterHelper  = new FormatterHelper($dateHelperMock, $translator);

        $this->excelExporter = new ExcelExporter($formatterHelper, $translator);
        $this->tmpFile       = tempnam(sys_get_temp_dir(), 'mautic_xlsx_export_test_');

        parent::setUp();
    }

    public function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws Exception
     */
    private function getExcelResult(): array
    {
        /** @var Xlsx $objReader */
        $objReader   = IOFactory::createReader('Xlsx');
        $spreadsheet = $objReader->load($this->tmpFile);

        return $spreadsheet->getActiveSheet()->toArray();
    }

    public function testExport(): void
    {
        $reportData       = Fixtures::getValidReportResultWithAggregatedColumns();
        $reportDataResult = new ReportDataResult($reportData);

        $this->excelExporter->export($reportDataResult, 'mautic_xlsx_export_test', $this->tmpFile);

        $result = $this->getExcelResult();

        $expected = [
            [
                'ID',
                'Name',
                'SUM Read',
                'AVG Read',
                'COUNT Contact ID',
            ],
            [
                '1',
                'Email 1',
                '50',
                '0.5',
                '100',
            ],
            [
                '2',
                'Email 2',
                '10',
                '0.1666',
                '60',
            ],
            [
                'Totals',
                null,
                '60',
                '0.3333',
                '160',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testExportEmptyData(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No report data to be exported');

        $reportData         = Fixtures::getValidReportResultWithAggregatedColumns();
        $reportData['data'] = [];
        $reportDataResult   = new ReportDataResult($reportData);

        $this->excelExporter->export($reportDataResult, 'mautic_xlsx_export_test', $this->tmpFile);
    }
}
