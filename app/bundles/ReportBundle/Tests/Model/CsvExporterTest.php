<?php

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Model\CsvExporter;
use Mautic\ReportBundle\Tests\Fixtures;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsvExporterTest extends \PHPUnit\Framework\TestCase
{
    public function testExport()
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $dateHelperMock =new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $translator,
           $coreParametersHelperMock
        );

        $formatterHelperMock = new FormatterHelper($dateHelperMock, $translator);

        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());

        $csvExporter = new CsvExporter($formatterHelperMock, $coreParametersHelperMock);

        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_csv_export_test_');
        $file    = fopen($tmpFile, 'w');

        $csvExporter->export($reportDataResult, $file);

        fclose($file);

        $result = array_map('str_getcsv', file($tmpFile));

        $expected = [
            [
                'City',
                'Company',
                'Country',
                'Date identified',
                'Email',
            ],
            [
                'City',
                '',
                '',
                '',
                '',
            ],
            [
                '',
                'Company',
                '',
                '',
                '',
            ],
            [
                '',
                '',
                'Country',
                '',
                '',
            ],
            [
                '',
                'ConnectWise',
                '',
                'October 10, 2017 12:00 am',
                'connectwise@example.com',
            ],
            [
                '',
                '',
                '',
                'October 10, 2017 12:00 am',
                'mytest@example.com',
            ],
            [
                '',
                '',
                '',
                'October 10, 2017 12:00 am',
                'john@example.com',
            ],
            [
                '',
                '',
                '',
                'October 10, 2017 12:00 am',
                'bogus@example.com',
            ],
            [
                '',
                '',
                '',
                'October 10, 2017 12:00 am',
                'date-test@example.com',
            ],
            [
                '',
                'Bodega Club',
                '',
                'October 10, 2017 12:00 am',
                'club@example.com',
            ],
            [
                '',
                '',
                '',
                'October 11, 2017 12:00 am',
                'test@example.com',
            ],
            [
                '',
                '',
                '',
                'October 12, 2017 12:00 am',
                'test@example.com',
            ],
        ];

        $this->assertSame($expected, $result);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }
}
