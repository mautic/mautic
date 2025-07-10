<?php

namespace Mautic\ReportBundle\Model;

use Mautic\ReportBundle\Scheduler\Model\FileHandler;

class ReportCleanup
{
    public const KEEP_FILE_DAYS = 7;

    public function __construct(private FileHandler $fileHandler)
    {
    }

    public function cleanup(int $reportId): void
    {
        if ($this->shouldBeDeleted($this->fileHandler->getPathToCompressedCsvFileForReportId($reportId))) {
            $this->fileHandler->deleteCompressedCsvFileForReportId($reportId);
        }
    }

    /**
     * Deletes files older than KEEP_FILE_DAYS.
     */
    public function cleanupAll(): void
    {
        $reportDirectory = $this->fileHandler->getCompressedCsvFileForReportDir();

        if (!file_exists($reportDirectory)) {
            return;
        }

        $files = array_diff(scandir($reportDirectory), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $reportDirectory.'/'.$file;
            if (is_dir($filePath)) {
                continue;
            }

            if ($this->shouldBeDeleted($filePath)) {
                $this->fileHandler->delete($filePath);
            }
        }
    }

    private function shouldBeDeleted(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $created = new \DateTime(date('Y-m-d', filemtime($filePath)));
        $now     = new \DateTime();
        $days    = $created->diff($now)->days;

        if ($days > self::KEEP_FILE_DAYS) {
            return true;
        }

        return false;
    }
}
