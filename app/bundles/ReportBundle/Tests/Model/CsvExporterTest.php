<?php

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Model\CsvExporter;
use Mautic\ReportBundle\Tests\Fixtures;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsvExporterTest extends \PHPUnit\Framework\TestCase
{
    public const DATEONLYFORMAT = 'F j, Y';

    public const TIMEONLYFORMAT          = 'g:i a';

    public function testExport()
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $dateHelperMock =new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            self::DATEONLYFORMAT,
            self::TIMEONLYFORMAT,
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
                '2017-10-10',
                'connectwise@example.com',
            ],
            [
                '',
                '',
                '',
                '2017-10-10',
                'mytest@example.com',
            ],
            [
                '',
                '',
                '',
                '2017-10-10',
                'john@example.com',
            ],
            [
                '',
                '',
                '',
                '2017-10-10',
                'bogus@example.com',
            ],
            [
                '',
                '',
                '',
                '2017-10-10',
                'date-test@example.com',
            ],
            [
                '',
                'Bodega Club',
                '',
                '2017-10-10',
                'club@example.com',
            ],
            [
                '',
                '',
                '',
                '2017-10-11',
                'test@example.com',
            ],
            [
                '',
                '',
                '',
                '2017-10-12',
                'test@example.com',
            ],
        ];

        $dateTimeHelper = new DateTimeHelper();
        foreach ($expected as $key => $expect) {
            if (0 === $key) {
                continue;
            }
            if (!empty($expect[3])) {
                $dateTimeHelper->setDateTime($expect[3]);
                $expected[$key][3] = $dateTimeHelper->toLocalString(
                    sprintf('%s %s', self::DATEONLYFORMAT, self::TIMEONLYFORMAT)
                );
            }
        }

        $this->assertSame($expected, $result);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }
}
