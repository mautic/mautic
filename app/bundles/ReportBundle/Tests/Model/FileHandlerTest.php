<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\FileProperties;
use Mautic\ReportBundle\Exception\FileTooBigException;
use Mautic\ReportBundle\Scheduler\Model\FileHandler;
use PHPUnit\Framework\TestCase;

final class FileHandlerTest extends TestCase
{
    public function testFileCanBeAttached(): void
    {
        $this->expectException(FileTooBigException::class);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $filePropertyMock = $this->createMock(FileProperties::class);

        $coreParametersHelperMock
            ->method('get')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn(0);

        $filePathResolver = $this->createStub(FilePathResolver::class);

        $filePropertyMock->expects($this->once())
            ->method('getFileSize')
            ->willReturn(5);

        $exportHandler = new FileHandler($filePathResolver, $filePropertyMock, $coreParametersHelperMock);

        $exportHandler->fileCanBeAttached('somefile');
    }
}
