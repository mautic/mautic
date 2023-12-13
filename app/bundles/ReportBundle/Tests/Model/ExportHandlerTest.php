<?php

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\ReportBundle\Exception\FileIOException;
use Mautic\ReportBundle\Model\ExportHandler;

class ExportHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandler(): void
    {
        $tmpDir = sys_get_temp_dir();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->any())
            ->method('get')
            ->with('report_temp_dir')
            ->willReturn($tmpDir);

        $filePathResolver = $this->getMockBuilder(FilePathResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filePathResolver->expects($this->once())
            ->method('createDirectory');

        $exportHandler = new ExportHandler($coreParametersHelperMock, $filePathResolver);

        $handler = $exportHandler->getHandler('myFile');
        $this->assertIsResource($handler);

        $exportHandler->closeHandler($handler);
        $this->assertIsClosedResource($handler);
    }

    public function testCreateDirectoryError(): void
    {
        $tmpDir = sys_get_temp_dir();

        $this->expectException(FileIOException::class);
        $this->expectExceptionMessage('Could not create directory '.$tmpDir);

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->any())
            ->method('get')
            ->with('report_temp_dir')
            ->willReturn($tmpDir);

        $filePathResolver = $this->getMockBuilder(FilePathResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filePathResolver->expects($this->once())
            ->method('createDirectory')
            ->willThrowException(new FilePathException());

        $exportHandler = new ExportHandler($coreParametersHelperMock, $filePathResolver);

        $exportHandler->getHandler('myFile');
    }

    public function testOpenFileError(): void
    {
        $tmpDir = 'xxx';

        $this->expectException(FileIOException::class);
        $this->expectExceptionMessage('Could not open file xxx/myFile.csv');

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->any())
            ->method('get')
            ->with('report_temp_dir')
            ->willReturn($tmpDir);

        $filePathResolver = $this->getMockBuilder(FilePathResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filePathResolver->expects($this->once())
            ->method('createDirectory');

        $exportHandler = new ExportHandler($coreParametersHelperMock, $filePathResolver);

        $exportHandler->getHandler('myFile');
    }
}
