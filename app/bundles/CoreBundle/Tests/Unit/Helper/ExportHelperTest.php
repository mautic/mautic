<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Exception;
use InvalidArgumentException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
use Mautic\LeadBundle\Model\LeadModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportHelperTest extends TestCase
{
    /** @var MockObject|TranslatorInterface */
    private $translatorInterfaceMock;
    /** @var MockObject|CoreParametersHelper */
    private $coreParametersHelperMock;
    private ExportHelper $exportHelper;
    /** @var array<array<string, int|string>> */
    private array $dummyData = [
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
    /** @var array<string> */
    private array $filePaths = [];

    protected function setUp(): void
    {
        $this->translatorInterfaceMock  = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->exportHelper             = new ExportHelper(
            $this->translatorInterfaceMock,
            $this->coreParametersHelperMock
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->filePaths as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        parent::tearDown();
    }

    /**
     * Test if exportDataAs() correctly generates a CSV file when we input some array data.
     */
    public function testCsvExport(): void
    {
        $stream = $this->exportHelper->exportDataAs($this->dummyData, ExportHelper::EXPORT_TYPE_CSV, 'demo-file.csv');
        Assert::assertSame(200, $stream->getStatusCode());
        Assert::assertFalse($stream->isEmpty());

        ob_start();
        $stream->sendContent();
        $content = ob_get_clean();

        $lines = explode(PHP_EOL, $this->removeBomUtf8($content));

        $this->assertSame('"id","firstname","lastname","email"', $lines[0]);
        $this->assertSame('"1","Mautibot","Mautic","mautibot@mautic.org"', $lines[1]);
        $this->assertSame('"2","Demo","Mautic","demo@mautic.org"', $lines[2]);
    }

    /**
     * Test if exportDataAs() correctly generates an Excel file when we input some array data.
     */
    public function testExcelExport(): void
    {
        $stream = $this->exportHelper->exportDataAs(
            $this->dummyData,
            ExportHelper::EXPORT_TYPE_EXCEL,
            'demo-file.xlsx'
        );
        Assert::assertSame(200, $stream->getStatusCode());
        Assert::assertFalse($stream->isEmpty());

        ob_start();
        $stream->sendContent();
        $content = ob_get_clean();

        // We need to write to a temp file as PhpSpreadsheet can only read from files
        file_put_contents('./demo-file.xlsx', $content);
        $spreadsheet = IOFactory::load('./demo-file.xlsx');
        unlink('./demo-file.xlsx');

        $this->assertSame(1, $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame('Mautibot', $spreadsheet->getActiveSheet()->getCell('B2')->getValue());
        $this->assertSame(2, $spreadsheet->getActiveSheet()->getCell('A3')->getValue());
        $this->assertSame('Demo', $spreadsheet->getActiveSheet()->getCell('B3')->getValue());
    }

    /**
     * @dataProvider getExportDataIntoFileProvider
     */
    public function testExportDataIntoFile(string $type, string $fileName, string $expectedFilePath): void
    {
        $this->coreParametersHelperMock
            ->method('get')
            ->with('contact_export_dir')
            ->willReturn('/tmp');
        $iteratorExportDataModelMock = $this->getIteratorExportDataModelMock();
        $this->filePaths[]           = $filePath = $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock,
            $type,
            $fileName
        );
        Assert::assertSame($filePath, $expectedFilePath);
    }

    /**
     * @dataProvider getExportDataIntoFileProvider
     */
    public function testExportDataIntoFileInvalidData(string $type, string $fileName): void
    {
        $this->expectException(Exception::class);
        $iteratorExportDataModelMock = $this->createMock(IteratorExportDataModel::class);
        $this->filePaths[]           = $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock,
            $type,
            $fileName
        );
    }

    /**
     * @return iterable<mixed>
     */
    public function getExportDataIntoFileProvider(): iterable
    {
        yield [ExportHelper::EXPORT_TYPE_CSV, 'contact_1.csv', '/tmp/contact_1.csv'];
        yield [ExportHelper::EXPORT_TYPE_EXCEL, 'contact_1.xlsx', '/tmp/contact_1.xlsx'];
    }

    public function testExportDataIntoExistingFile(): void
    {
        $this->coreParametersHelperMock
            ->method('get')
            ->with('contact_export_dir')
            ->willReturn('/tmp');
        $iteratorExportDataModelMock1 = $this->getIteratorExportDataModelMock();
        $filePath1                    = $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock1,
            ExportHelper::EXPORT_TYPE_CSV,
            'contact_1.csv'
        );
        Assert::assertSame($filePath1, '/tmp/contact_1.csv');

        $iteratorExportDataModelMock2 = $this->getIteratorExportDataModelMock();
        $filePath2                    = $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock2,
            ExportHelper::EXPORT_TYPE_CSV,
            'contact_1.csv'
        );
        Assert::assertSame($filePath2, '/tmp/contact_1_1.csv');
        $this->filePaths[] = $filePath1;
        $this->filePaths[] = $filePath2;
    }

    public function testExportDataIntoFileInvalidType(): void
    {
        $type = 'txt';
        $this->expectException(InvalidArgumentException::class);
        $iteratorExportDataModelMock = $this->getIteratorExportDataModelMock();
        $this->translatorInterfaceMock->expects(self::once())
            ->method('trans')
            ->with('mautic.error.invalid.export.type', ['%type%' => $type])
            ->willReturn('Invalid export type "'.$type.'" Must be one of "csv" or "xlsx".');
        $this->filePaths[] = $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock,
            $type,
            'contact_1.csv'
        );
    }

    /**
     * Needed to remove the BOM that we add in our CSV exports (for UTF-8 parsing in Excel).
     */
    private function removeBomUtf8(string $s): string
    {
        if (substr($s, 0, 3) == chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))) {
            return substr($s, 3);
        }

        return $s;
    }

    private function getIteratorExportDataModelMock(): IteratorExportDataModel
    {
        $leadModelMock = $this->createMock(LeadModel::class);
        $leadModelMock
            ->method('getEntities')
            ->willReturn(['results' => []]);
        $args          = ['limit' => 1000];
        $callback      = function ($var) {
            return $var;
        };

        return new IteratorExportDataModel($leadModelMock, $args, $callback);
    }
}
