<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\ReportBundle\Exception\FileIOException;
use Mautic\ReportBundle\Model\ExportHandler;

class ExportHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandler()
    {
        $tmpDir = sys_get_temp_dir();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->any())
            ->method('getParameter')
            ->with('report_temp_dir')
            ->willReturn($tmpDir);

        $filePathResolver = $this->getMockBuilder(FilePathResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filePathResolver->expects($this->once())
            ->method('createDirectory');

        $exportHandler = new ExportHandler($coreParametersHelperMock, $filePathResolver);

        $handler = $exportHandler->getHandler('myFile');

        $this->assertTrue(is_resource($handler));

        $handler = $exportHandler->closeHandler($handler);

        $this->assertFalse(is_resource($handler));
        $this->assertNull($handler);
    }

    public function testCreateDirectoryError()
    {
        $tmpDir = sys_get_temp_dir();

        $this->expectException(FileIOException::class);
        $this->expectExceptionMessage('Could not create directory '.$tmpDir);

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->any())
            ->method('getParameter')
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

    public function testOpenFileError()
    {
        $tmpDir = 'xxx';

        $this->expectException(FileIOException::class);
        $this->expectExceptionMessage('Could not open file xxx/myFile.csv');

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->any())
            ->method('getParameter')
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
