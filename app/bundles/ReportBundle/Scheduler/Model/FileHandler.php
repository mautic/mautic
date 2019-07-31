<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Scheduler\Model;

use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\FileProperties;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Exception\FileTooBigException;

class FileHandler
{
    /**
     * @const string
     */
    const REPORTS_DIR = 'csv_reports';

    public function __construct(
        private FilePathResolver $filePathResolver,
        private FileProperties $fileProperties,
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    /**
     * @throws FileInvalidException
     * @throws FileTooBigException
     */
    public function fileCanBeAttached(string $filePath): void
    {
        $fileSize    = $this->fileProperties->getFileSize($filePath);
        $maxFileSize = (int) $this->coreParametersHelper->get('report_export_max_filesize_in_bytes');

        if ($fileSize > $maxFileSize) {
            throw new FileTooBigException("File {$filePath} has {$fileSize} bytes which is more than the limit of {$maxFileSize} bytes.");
        }
    }

    /**
     * Zips the file and returns the path where the zip file was created.
     *
     * @throws FilePathException
     */
    public function zipIt(string $originalFilePath): string
    {
        $zipFilePath = str_replace('.csv', '.zip', $originalFilePath);
        $zipArchive  = new \ZipArchive();

        if (true === $zipArchive->open($zipFilePath, \ZipArchive::OVERWRITE | \ZipArchive::CREATE)) {
            $zipArchive->addFile($originalFilePath, 'report.csv');
            $zipArchive->close();

            return $zipFilePath;
        }

        throw new FilePathException("Could not create zip archive at {$zipFilePath}. {$zipArchive->getStatusString()}");
    }

    public function getPathToCompressedCsvFileForReport(Report $report): string
    {
        return $this->getPathToCompressedCsvFileForReportId($report->getId());
    }

    /**
     * @param int $reportId
     *
     * @return string;
     */
    public function getPathToCompressedCsvFileForReportId($reportId)
    {
        return $this->getCompressedCsvFileForReportDir()."/report_{$reportId}.zip";
    }

    /**
     * @codeCoverageIgnore as it calls PHP function only.
     */
    public function compressedCsvFileForReportExists(Report $report): bool
    {
        $filePath = $this->getPathToCompressedCsvFileForReport($report);

        return file_exists($filePath);
    }

    public function moveZipToPermanentLocation(Report $report, string $originalPath): void
    {
        $compressedCsvPath = $this->getPathToCompressedCsvFileForReport($report);

        $this->filePathResolver->delete($compressedCsvPath);
        $this->filePathResolver->createDirectory(dirname($compressedCsvPath));
        $this->filePathResolver->move($originalPath, $compressedCsvPath);
    }

    /**
     * @param string $filePath
     */
    public function delete($filePath)
    {
        $this->filePathResolver->delete($filePath);
    }

    /**
     * @param int $reportId
     */
    public function deleteCompressedCsvFileForReportId($reportId)
    {
        $filePath = $this->getPathToCompressedCsvFileForReportId($reportId);
        if (file_exists($filePath)) {
            $this->delete($filePath);
        }
    }

    /**
     * @return string
     */
    public function getCompressedCsvFileForReportDir()
    {
        $reportDir = $this->coreParametersHelper->get('report_temp_dir');
        return $reportDir.'/'.self::REPORTS_DIR;
    }
}
