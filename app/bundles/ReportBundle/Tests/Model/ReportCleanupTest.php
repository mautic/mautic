<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\ReportBundle\Model\ReportCleanup;
use Mautic\ReportBundle\Scheduler\Model\FileHandler;

class ReportCleanupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var ReportCleanup
     */
    private $cleanup;

    protected function setUp()
    {
        $this->fileHandler = $this->createMock(FileHandler::class);
        $this->cleanup     =  new ReportCleanup($this->fileHandler);
    }

    public function testCleanupAll()
    {
        $reportIds  = [11, 13, 33];
        $reportsDir = sys_get_temp_dir().'/csv_reports';

        if (false === file_exists($reportsDir)) {
            mkdir($reportsDir);
        }

        $filePaths = [];

        foreach ($reportIds as $reportId) {
            $filePath     = $this->getFilePath($reportsDir, $reportId);
            $filePaths[]  = $filePath;

            $days = ReportCleanup::KEEP_FILE_DAYS + 1;

            // this report shouldn't be deleted
            if ($reportId === 33) {
                $days = ReportCleanup::KEEP_FILE_DAYS - 1;
            }

            $modifiedDate = time() - (86400 * $days);
            $this->createTmpFile($filePath, $modifiedDate);
        }

        $this->fileHandler->expects($this->once())
            ->method('getCompressedCsvFileForReportDir')
            ->willReturn($reportsDir);

        $this->fileHandler->expects($this->exactly(2))
            ->method('delete');

        $this->fileHandler->expects($this->at(1))
            ->method('delete')
            ->with($filePaths[0]);

        $this->fileHandler->expects($this->at(2))
            ->method('delete')
            ->with($filePaths[1]);

        $this->cleanup->cleanupAll();

        foreach ($filePaths as $filePath) {
            unlink($filePath);
        }

        rmdir($reportsDir);
    }

    public function testCleanup()
    {
        $reportId   = 9;
        $reportsDir = sys_get_temp_dir().'/csv_reports';

        if (false === file_exists($reportsDir)) {
            mkdir($reportsDir);
        }

        $filePath     = $this->getFilePath($reportsDir, $reportId);
        $days         = ReportCleanup::KEEP_FILE_DAYS + 1;
        $modifiedDate = time() - (86400 * $days);

        $this->createTmpFile($filePath, $modifiedDate);

        $this->fileHandler->expects($this->once())
            ->method('deleteCompressedCsvFileForReportId')
            ->with($reportId);

        $this->fileHandler->expects($this->once())
            ->method('getPathToCompressedCsvFileForReportId')
            ->with($reportId)
            ->willReturn($filePath);

        $this->cleanup->cleanup($reportId);

        unlink($filePath);
        rmdir($reportsDir);
    }

    private function createTmpFile($filePath, $modifiedDate = null, $content = '')
    {
        file_put_contents($filePath, $content);
        if (null !== $modifiedDate) {
            touch($filePath, $modifiedDate);
        }

        return $filePath;
    }

    private function getFilePath($reportsDir, $reportId)
    {
        $fileName     = "report_{$reportId}.zip";
        $filePath     = $reportsDir.'/'.$fileName;

        return $filePath;
    }
}
