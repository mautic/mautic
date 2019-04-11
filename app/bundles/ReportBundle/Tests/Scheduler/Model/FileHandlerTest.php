<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\FileProperties;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Exception\FileTooBigException;
use Mautic\ReportBundle\Scheduler\Model\FileHandler;

class FileHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $filePathResolver;
    private $fileProperties;
    private $coreParametersHelper;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    protected function setUp()
    {
        $this->filePathResolver     = $this->createMock(FilePathResolver::class);
        $this->fileProperties       = $this->createMock(FileProperties::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->fileHandler          = new FileHandler(
            $this->filePathResolver,
            $this->fileProperties,
            $this->coreParametersHelper
        );
    }

    public function testFileCanBeAttachedIfTrue()
    {
        $filePath  = 'file/path.csv';
        $fileSize  = 1000;
        $fileLimit = 5000;

        $this->fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with($filePath)
            ->willReturn($fileSize);

        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($fileLimit);

        $this->fileHandler->fileCanBeAttached($filePath);
    }

    public function testFileCanBeAttachedIfFalse()
    {
        $filePath  = 'file/path.csv';
        $fileSize  = 10000;
        $fileLimit = 5000;

        $this->fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with($filePath)
            ->willReturn($fileSize);

        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($fileLimit);

        $this->expectException(FileTooBigException::class);

        $this->fileHandler->fileCanBeAttached($filePath);
    }

    public function testZipIt()
    {
        $tmpFilePath    = $this->createTmpFile();
        $tmpZipFilePath = $this->fileHandler->zipIt($tmpFilePath);

        $this->assertFileExists($tmpZipFilePath);

        unlink($tmpFilePath);
        unlink($tmpZipFilePath);
    }

    public function testGetPathToCompressedCsvFileForReport()
    {
        $report = $this->createMock(Report::class);

        $report->expects($this->once())
            ->method('getId')
            ->willReturn(33);

        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_temp_dir')
            ->willReturn('/some/path');

        $filePath = $this->fileHandler->getPathToCompressedCsvFileForReport($report);

        $this->assertSame('/some/path/csv_reports/report_33.zip', $filePath);
    }

    public function testMoveZipToPermanentLocation()
    {
        $report   = $this->createMock(Report::class);
        $filePath = 'file/path.csv';

        $report->expects($this->once())
            ->method('getId')
            ->willReturn(33);

        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_temp_dir')
            ->willReturn('/some/path');

        $this->filePathResolver->expects($this->once())
            ->method('delete')
            ->with('/some/path/csv_reports/report_33.zip');

        $this->filePathResolver->expects($this->once())
            ->method('createDirectory')
            ->with('/some/path/csv_reports');

        $this->filePathResolver->expects($this->once())
            ->method('move')
            ->with($filePath, '/some/path/csv_reports/report_33.zip');

        $this->fileHandler->moveZipToPermanentLocation($report, $filePath);
    }

    private function createTmpFile($name = 'test.csv', $content = '')
    {
        $filePath = sys_get_temp_dir().'/'.$name;
        file_put_contents($filePath, $content);

        return $filePath;
    }
}
