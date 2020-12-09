<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ExportHelper;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\TranslatorInterface;

class ExportHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface */
    private $translatorInterfaceMock;

    private $dummyData = [
        [
            'id'        => 1,
            'firstname' => 'Mautibot',
            'lastname'  => 'Mautic',
            'email'     => 'mautibot@mautic.org',
        ],
        [
            'id'        => 2,
            'firstname' => 'Demo',
            'lastname'  => 'Mautic',
            'email'     => 'demo@mautic.org',
        ],
    ];

    protected function setUp(): void
    {
        $this->translatorInterfaceMock = $this->createMock(TranslatorInterface::class);
    }

    /**
     * Test if exportDataAs() correctly generates a CSV file when we input some array data.
     */
    public function testCsvExport()
    {
        $helper = $this->getExportHelper();
        $stream = $helper->exportDataAs($this->dummyData, ExportHelper::EXPORT_TYPE_CSV, 'demo-file.csv');

        $this->assertInstanceOf(StreamedResponse::class, $stream);
        $this->assertSame(200, $stream->getStatusCode());
        $this->assertSame(false, $stream->isEmpty());

        ob_start();
        $stream->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $lines = explode(PHP_EOL, $this->removeBomUtf8($content));

        $this->assertSame('"id","firstname","lastname","email"', $lines[0]);
        $this->assertSame('"1","Mautibot","Mautic","mautibot@mautic.org"', $lines[1]);
        $this->assertSame('"2","Demo","Mautic","demo@mautic.org"', $lines[2]);
    }

    /**
     * Test if exportDataAs() correctly generates an Excel file when we input some array data.
     */
    public function testExcelExport()
    {
        $helper = $this->getExportHelper();
        $stream = $helper->exportDataAs($this->dummyData, ExportHelper::EXPORT_TYPE_EXCEL, 'demo-file.xlsx');

        $this->assertInstanceOf(StreamedResponse::class, $stream);
        $this->assertSame(200, $stream->getStatusCode());
        $this->assertSame(false, $stream->isEmpty());

        ob_start();
        $stream->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        // We need to write to a temp file as PhpSpreadsheet can only read from files
        file_put_contents('./demo-file.xlsx', $content);
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('./demo-file.xlsx');
        unlink('./demo-file.xlsx');

        $this->assertSame(1, $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame('Mautibot', $spreadsheet->getActiveSheet()->getCell('B2')->getValue());
        $this->assertSame(2, $spreadsheet->getActiveSheet()->getCell('A3')->getValue());
        $this->assertSame('Demo', $spreadsheet->getActiveSheet()->getCell('B3')->getValue());
    }

    private function getExportHelper(): ExportHelper
    {
        return new ExportHelper(
            $this->translatorInterfaceMock
        );
    }

    /**
     * Needed to remove the BOM that we add in our CSV exports (for UTF-8 parsing in Excel).
     */
    private function removeBomUtf8(string $s): string
    {
        if (substr($s, 0, 3) == chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))) {
            return substr($s, 3);
        } else {
            return $s;
        }
    }
}
