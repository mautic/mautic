<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Exception;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
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
    /** @var FilePathResolver|MockObject */
    private $filePathResolver;

    protected function setUp(): void
    {
        $this->translatorInterfaceMock  = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->filePathResolver         = $this->createMock(FilePathResolver::class);
        $this->exportHelper             = new ExportHelper(
            $this->translatorInterfaceMock,
            $this->coreParametersHelperMock,
            $this->filePathResolver
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
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No or invalid data given');
        $this->exportHelper->exportDataAs([], ExportHelper::EXPORT_TYPE_EXCEL, 'demo.xlsx');
    }

    public function testExportDataAsInvalidFileType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->translatorInterfaceMock->expects($this->once())
            ->method('trans')
            ->with('mautic.error.invalid.specific.export.type', [
                '%type%'          => ExportHelper::EXPORT_TYPE_CSV,
                '%expected_type%' => ExportHelper::EXPORT_TYPE_EXCEL,
            ])
            ->willReturn(
                'Invalid export type "'.ExportHelper::EXPORT_TYPE_CSV.
                '". Must be of "'.ExportHelper::EXPORT_TYPE_EXCEL.'".'
            );
        $this->exportHelper->exportDataAs($this->dummyData, ExportHelper::EXPORT_TYPE_CSV, 'demo.csv');
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
        $this->expectException(Exception::class);
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
        $this->translatorInterfaceMock->expects($this->once())
            ->method('trans')
            ->with('mautic.error.invalid.specific.export.type', [
                '%type%'          => ExportHelper::EXPORT_TYPE_EXCEL,
                '%expected_type%' => ExportHelper::EXPORT_TYPE_CSV,
            ])
            ->willReturn(
                'Invalid export type "'.ExportHelper::EXPORT_TYPE_EXCEL.
                '". Must be of "'.ExportHelper::EXPORT_TYPE_CSV.'".'
            );
        $iteratorExportDataModelMock = $this->iteratorDataMock($this->dummyData);
        $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock,
            ExportHelper::EXPORT_TYPE_EXCEL,
            'demo.xlsx'
        );
    }

    public function testExportDataIntoFileCsvWithExistingFileName(): void
    {
        $this->coreParametersHelperMock
            ->method('get')
            ->with('contact_export_dir')
            ->willReturn('/tmp');

        $this->filePathResolver
            ->method('createDirectory')
            ->with('/tmp');

        $iteratorExportDataModelMock1 = $this->iteratorDataMock($this->dummyData);
        $this->filePaths[]            = $filePath  = $this->exportHelper->exportDataIntoFile(
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
        $this->filePaths[]            = $filePath  = $this->exportHelper->exportDataIntoFile(
            $iteratorExportDataModelMock2,
            ExportHelper::EXPORT_TYPE_CSV,
            'demo.csv' // give same file name
        );
        Assert::assertSame('/tmp/demo_1.csv', $filePath);
        Assert::assertFileExists($filePath);
        $spreadsheet = IOFactory::load('/tmp/demo_1.csv');
        $this->assertSame(1, $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame('Mautibot', $spreadsheet->getActiveSheet()->getCell('B2')->getValue());
        $this->assertSame(2, $spreadsheet->getActiveSheet()->getCell('A3')->getValue());
        $this->assertSame('Demo', $spreadsheet->getActiveSheet()->getCell('B3')->getValue());
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

        $iteratorExportDataModelMock->expects($this->any())
            ->method('rewind')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    $iteratorData->position = 0;
                }
            );

        $iteratorExportDataModelMock->expects($this->any())
            ->method('current')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    return $iteratorData->array[$iteratorData->position];
                }
            );

        $iteratorExportDataModelMock->expects($this->any())
            ->method('key')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    return $iteratorData->position;
                }
            );

        $iteratorExportDataModelMock->expects($this->any())
            ->method('next')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    ++$iteratorData->position;
                }
            );

        $iteratorExportDataModelMock->expects($this->any())
            ->method('valid')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    return isset($iteratorData->array[$iteratorData->position]);
                }
            );

        return $iteratorExportDataModelMock;
    }
}
