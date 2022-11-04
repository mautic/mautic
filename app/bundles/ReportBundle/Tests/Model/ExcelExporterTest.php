<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\ReportBundle\Model\ExcelExporter;
use Mautic\ReportBundle\Tests\Fixtures;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExcelExporterTest extends TestCase
{
    public function testExport(): void
    {
        $dateHelperMock   = $this->createMock(DateHelper::class);
        $translator       = $this->createMock(TranslatorInterface::class);
        $formatterHelper  = new FormatterHelper($dateHelperMock, $translator);
        $reportDataResult = Fixtures::getValidReportResultWithAggregatedColumns();
        $excelExporter    = new ExcelExporter($formatterHelper);

        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_xlsx_export_test_');
        $excelExporter->export($reportDataResult, 'mautic_xlsx_export_test', $tmpFile);

        /** @var Xlsx $objReader */
        $objReader   = IOFactory::createReader('Xlsx');
        $spreadsheet = $objReader->load($tmpFile);
        $result      = $spreadsheet->getActiveSheet()->toArray();

        $expected = [
            [
                'ID',
                'Name',
                'SUM Read',
                'AVG Read',
                'COUNT Contact ID',
            ],
            [
                1,
                'Email 1',
                50,
                0.5,
                100,
            ],
            [
                2,
                'Email 2',
                10,
                0.1666,
                60,
            ],
        ];

        $this->assertSame($expected, $result);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }
}
