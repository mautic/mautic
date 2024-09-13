<?php

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\ReportBundle\Model\ReportCleanup;
use Mautic\ReportBundle\Scheduler\Model\FileHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReportCleanupTest extends TestCase
{
    private MockObject|FileHandler $fileHandler;

    private ReportCleanup $cleanup;

    protected function setUp(): void
    {
        $this->fileHandler = $this->createMock(FileHandler::class);
        $this->cleanup     =  new ReportCleanup($this->fileHandler);
    }

    public function testCleanupAll(): void
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
            if (33 === $reportId) {
                $days = ReportCleanup::KEEP_FILE_DAYS - 1;
            }

            $modifiedDate = time() - (86400 * $days);
            $this->createTmpFile($filePath, $modifiedDate);
        }

        $this->fileHandler->expects($this->once())
            ->method('getCompressedCsvFileForReportDir')
            ->willReturn($reportsDir);

        $this->fileHandler->expects($this->exactly(2))
            ->method('delete')
            ->willReturnOnConsecutiveCalls($filePaths[0], $filePaths[1]);

        $this->cleanup->cleanupAll();

        foreach ($filePaths as $filePath) {
            unlink($filePath);
        }

        rmdir($reportsDir);
    }

    public function testCleanup(): void
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

    private function createTmpFile(string $filePath, ?int $modifiedDate = null, string $content = ''): string
    {
        file_put_contents($filePath, $content);
        if (null !== $modifiedDate) {
            touch($filePath, $modifiedDate);
        }

        return $filePath;
    }

    private function getFilePath(string $reportsDir, int $reportId): string
    {
        return $reportsDir.'/'."report_{$reportId}.zip";
    }
}
