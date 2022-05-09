<?php

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\FileProperties;
use Mautic\ReportBundle\Exception\FileTooBigException;
use Mautic\ReportBundle\Scheduler\Model\FileHandler;

class FileHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testFileCanBeAttached(): void
    {
        $this->expectException(FileTooBigException::class);
        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filePropertyMock = $this->getMockBuilder(FileProperties::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->any())
            ->method('get')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn(0);

        $filePathResolver = $this->getMockBuilder(FilePathResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filePropertyMock->expects($this->once())
            ->method('getFileSize')
            ->willReturn(5);

        $exportHandler = new FileHandler($filePathResolver, $filePropertyMock, $coreParametersHelperMock);

        $exportHandler->fileCanBeAttached('somefile');
    }
}
