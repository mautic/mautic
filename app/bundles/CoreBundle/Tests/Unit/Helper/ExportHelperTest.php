<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Stage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportHelperTest extends TestCase
{
    private MockObject&TranslatorInterface $translatorInterfaceMock;

    private MockObject&CoreParametersHelper $coreParametersHelperMock;

    private ExportHelper $exportHelper;

    /**
     * @var array<array<string, int|string>>
     */
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

    /**
     * @var array<string>
     */
    private array $filePaths = [];

    private MockObject&FilePathResolver $filePathResolver;
    private MockObject&ProcessSignalService $processSignalService;

    protected function setUp(): void
    {
        $this->translatorInterfaceMock  = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->filePathResolver         = $this->createMock(FilePathResolver::class);
        $this->processSignalService     = $this->createMock(ProcessSignalService::class);

        $this->exportHelper             = new ExportHelper(
            $this->translatorInterfaceMock,
            $this->coreParametersHelperMock,
            $this->filePathResolver,
            $this->processSignalService,
            $this->createMock(EventDispatcherInterface::class),
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

    public function testDownloadAsZip(): void
    {
        $tempDir     = sys_get_temp_dir();
        $zipFilePath = $tempDir.'/test-download.zip';
        $zip         = new \ZipArchive();
        $zip->open($zipFilePath, \ZipArchive::CREATE);
        $zip->addFromString('test.txt', 'This is test content');
        $zip->close();

        $this->filePaths[] = $zipFilePath;

        $response = $this->exportHelper->downloadAsZip($zipFilePath, 'exported.zip');

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="exported.zip"', $response->headers->get('Content-Disposition'));
    }

    public function testWriteToZipFileIncludesAssets(): void
    {
        $tempDir = sys_get_temp_dir();

        // Create temporary asset files
        $assetFilePath1 = tempnam($tempDir, 'asset_test1');
        file_put_contents($assetFilePath1, 'Asset content 1');

        $assetFilePath2 = tempnam($tempDir, 'asset_test2');
        file_put_contents($assetFilePath2, 'Asset content 2');

        $assetList  = [$assetFilePath1, $assetFilePath2];
        $jsonOutput = json_encode(['key' => 'value']);

        // Call the method
        $zipFilePath = $this->exportHelper->writeToZipFile($jsonOutput, $assetList, '');

        // Open the ZIP file and verify contents
        $zip = new \ZipArchive();
        $zip->open($zipFilePath);

        $this->assertTrue(false !== $zip->locateName('entity_data.json'));
        $this->assertTrue(false !== $zip->locateName('assets/'.basename($assetFilePath1)));
        $this->assertTrue(false !== $zip->locateName('assets/'.basename($assetFilePath2)));

        $zip->close();

        // Clean up
        unlink($assetFilePath1);
        unlink($assetFilePath2);
        unlink($zipFilePath);
    }

    public function testWriteToZipFileIncludesAssetsWithCustomPath(): void
    {
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $tempDir    = sys_get_temp_dir();
        $customDir  = $tempDir.'/export_test_'.uniqid();
        $filesystem->mkdir($customDir);

        // Create temporary asset files
        $assetFilePath1 = tempnam($tempDir, 'asset_test1');
        file_put_contents($assetFilePath1, 'Asset content 1');

        $assetFilePath2 = tempnam($tempDir, 'asset_test2');
        file_put_contents($assetFilePath2, 'Asset content 2');

        $assetList  = [$assetFilePath1, $assetFilePath2];
        $jsonOutput = json_encode(['key' => 'value']);

        // Pre-create entity_data.json to trigger unlink in method
        $jsonFilePath = sprintf('%s/entity_data.json', $customDir);
        file_put_contents($jsonFilePath, 'Old content');
        $this->assertFileExists($jsonFilePath);

        // Call the method with custom path
        $zipFilePath = $this->exportHelper->writeToZipFile($jsonOutput, $assetList, $customDir);

        $this->assertFileExists($zipFilePath);
        $this->assertStringStartsWith($customDir, $zipFilePath);

        // Open the ZIP file and verify contents
        $zip = new \ZipArchive();
        $zip->open($zipFilePath);

        $this->assertNotFalse($zip->locateName('entity_data.json'));
        $this->assertNotFalse($zip->locateName('assets/'.basename($assetFilePath1)));
        $this->assertNotFalse($zip->locateName('assets/'.basename($assetFilePath2)));

        $zip->close();
        $this->assertFileDoesNotExist($jsonFilePath);

        // Clean up using Symfony Filesystem
        $filesystem->remove([$assetFilePath1, $assetFilePath2, $zipFilePath, $customDir]);
    }

    /**
     * Test if exportDataAs() correctly generates a CSV file when we input some array data.
     */
    public function testCsvExport(): void
    {
        $stream = $this->exportHelper->exportDataAs($this->dummyData, ExportHelper::EXPORT_TYPE_CSV, 'demo-file.csv');

        $this->assertSame(200, $stream->getStatusCode());
        $this->assertFalse($stream->isEmpty());

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
    public function testExcelExport(): void
    {
        $stream = $this->exportHelper->exportDataAs($this->dummyData, ExportHelper::EXPORT_TYPE_EXCEL, 'demo-file.xlsx');

        $this->assertSame(200, $stream->getStatusCode());
        $this->assertFalse($stream->isEmpty());

        ob_start();
        $stream->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        // We need to write to a temp file as PhpSpreadsheet can only read from files
        file_put_contents('./demo-file.xlsx', $content);
        $spreadsheet = IOFactory::load('./demo-file.xlsx');
        unlink('./demo-file.xlsx');

        $this->assertSame(1, $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame('Mautibot', $spreadsheet->getActiveSheet()->getCell('B2')->getValue());
        $this->assertSame(2, $spreadsheet->getActiveSheet()->getCell('A3')->getValue());
        $this->assertSame('Demo', $spreadsheet->getActiveSheet()->getCell('B3')->getValue());
    }

    public function testParseLeadResults(): void
    {
        $leadFieldsData = [
            'id'        => 43,
            'email'     => 'tomasz.amg@example.com',
            'firstname' => 'Tomasz',
            'lastname'  => 'Amg',
        ];

        $lead = new Lead();
        $lead->setFields($leadFieldsData);

        $stage = new Stage();
        $stage->setName('Stage 3');
        $lead->setStage($stage);

        $result   = $this->exportHelper->parseLeadToExport($lead);
        $expected = $leadFieldsData + ['stage' => 'Stage 3'];
        $this->assertEquals($expected, $result);
    }

    public function testSupportedExportTypes(): void
    {
        $fileTypes = [
            ExportHelper::EXPORT_TYPE_CSV,
            ExportHelper::EXPORT_TYPE_EXCEL,
        ];
        Assert::assertSame($fileTypes, $this->exportHelper->getSupportedExportTypes());
    }

    public function testExportDataAsInvalidData(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No or invalid data given');
        $this->exportHelper->exportDataAs([], ExportHelper::EXPORT_TYPE_EXCEL, 'demo.xlsx');
    }

    public function testExportDataAsInvalidFileType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->translatorInterfaceMock->expects($this->once())->method('trans')
            ->with(
                'mautic.error.invalid.specific.export.type', [
                    '%type%'          => 'xls',
                    '%expected_type%' => ExportHelper::EXPORT_TYPE_EXCEL,
                ]
            )->willReturn(
                'Invalid export type "xls". Must be of "'.
                ExportHelper::EXPORT_TYPE_EXCEL.'".'
            );
        $this->exportHelper->exportDataAs($this->dummyData, 'xls', 'demo.xls');
    }

    public function testExportDataAsExcel(): void
    {
        $stream = $this->exportHelper->exportDataAs($this->dummyData, ExportHelper::EXPORT_TYPE_EXCEL, 'demo.xlsx');
        Assert::assertSame(200, $stream->getStatusCode());
        Assert::assertFalse($stream->isEmpty());

        ob_start();
        $stream->sendContent();
        $content = ob_get_clean();

        // We need to write to a temp file as PhpSpreadsheet can only read from files
        file_put_contents('demo.xlsx', $content);
        $spreadsheet       = IOFactory::load('demo.xlsx');
        $this->filePaths[] = 'demo.xlsx';

        $this->assertSame(1, $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame('Mautibot', $spreadsheet->getActiveSheet()->getCell('B2')->getValue());
        $this->assertSame(2, $spreadsheet->getActiveSheet()->getCell('A3')->getValue());
        $this->assertSame('Demo', $spreadsheet->getActiveSheet()->getCell('B3')->getValue());
    }

    public function testExportDataIntoFileInvalidData(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No or invalid data given');
        $iteratorExportDataModelMock = $this->iteratorDataMock();
        $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock,
            ExportHelper::EXPORT_TYPE_CSV,
            'demo.csv'
        );
    }

    public function testExportDataIntoFileInvalidFileType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->translatorInterfaceMock->expects($this->once())->method('trans')->with(
            'mautic.error.invalid.specific.export.type', [
                '%type%'          => ExportHelper::EXPORT_TYPE_EXCEL,
                '%expected_type%' => ExportHelper::EXPORT_TYPE_CSV,
            ]
        )->willReturn(
            'Invalid export type "'.ExportHelper::EXPORT_TYPE_EXCEL.'". Must be of "'.ExportHelper::EXPORT_TYPE_CSV.'".'
        );
        $iteratorExportDataModelMock = $this->iteratorDataMock($this->dummyData);
        $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock,
            ExportHelper::EXPORT_TYPE_EXCEL,
            'demo.xlsx'
        );
    }

    public function testExportDataIntoFileCsvWithExistingFileNameWithZip(): void
    {
        $this->coreParametersHelperMock->method('get')->with('contact_export_dir')->willReturn('/tmp');

        $this->filePathResolver->method('createDirectory')->with('/tmp');

        $iteratorExportDataModelMock1 = $this->iteratorDataMock($this->dummyData);
        $this->filePaths[]            = $filePath = $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock1,
            ExportHelper::EXPORT_TYPE_CSV,
            'demo.csv'
        );
        Assert::assertFileExists($filePath);
        $spreadsheet = IOFactory::load('/tmp/demo.csv');
        $this->assertSame(1, $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame('Mautibot', $spreadsheet->getActiveSheet()->getCell('B2')->getValue());
        $this->assertSame(2, $spreadsheet->getActiveSheet()->getCell('A3')->getValue());
        $this->assertSame('Demo', $spreadsheet->getActiveSheet()->getCell('B3')->getValue());

        $iteratorExportDataModelMock2 = $this->iteratorDataMock($this->dummyData);
        $this->filePaths[]            = $filePath2 = $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock2,
            ExportHelper::EXPORT_TYPE_CSV,
            'demo.csv' // give same file name
        );
        Assert::assertSame('/tmp/demo_1.csv', $filePath2);
        Assert::assertFileExists($filePath2);
        $spreadsheet = IOFactory::load('/tmp/demo_1.csv');
        $this->assertSame(1, $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame('Mautibot', $spreadsheet->getActiveSheet()->getCell('B2')->getValue());
        $this->assertSame(2, $spreadsheet->getActiveSheet()->getCell('A3')->getValue());
        $this->assertSame('Demo', $spreadsheet->getActiveSheet()->getCell('B3')->getValue());

        $this->filePaths[] = $zipFilePath = $this->exportHelper->zipFile($filePath, 'contacts_export.csv');
        Assert::assertFileExists($zipFilePath);
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

    /**
     * @param array<mixed> $data
     */
    private function iteratorDataMock(array $data = []): IteratorExportDataModel
    {
        $iteratorExportDataModelMock = $this->createMock(IteratorExportDataModel::class);
        $iteratorData                = new \stdClass();
        $iteratorData->array         = $data;
        $iteratorData->position      = 0;

        $iteratorExportDataModelMock->method('rewind')
            ->willReturnCallback(
                function () use ($iteratorData): void {
                    $iteratorData->position = 0;
                }
            );

        $iteratorExportDataModelMock->method('current')
            ->willReturnCallback(
                fn () => $iteratorData->array[$iteratorData->position]
            );

        $iteratorExportDataModelMock->method('key')
            ->willReturnCallback(
                fn () => $iteratorData->position
            );

        $iteratorExportDataModelMock->method('next')
            ->willReturnCallback(
                function () use ($iteratorData): void {
                    ++$iteratorData->position;
                }
            );

        $iteratorExportDataModelMock->method('valid')
            ->willReturnCallback(
                fn () => isset($iteratorData->array[$iteratorData->position])
            );

        return $iteratorExportDataModelMock;
    }
}
