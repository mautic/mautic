<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileProperties;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\ReportBundle\Exception\FileTooBigException;

class FileHandler
{
    /**
     * @var FilePathResolver
     */
    private $filePathResolver;

    /**
     * @var FileProperties
     */
    private $fileProperties;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(
        FilePathResolver $filePathResolver,
        FileProperties $fileProperties,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->filePathResolver     = $filePathResolver;
        $this->fileProperties       = $fileProperties;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     *
     * @throws FileInvalidException
     * @throws FileTooBigException
     */
    public function fileCanBeAttached($filePath)
    {
        $fileSize    = $this->fileProperties->getFileSize($filePath);
        $maxFileSize = (int) $this->coreParametersHelper->getParameter('report_export_max_filesize_in_bytes');

        if ($fileSize > $maxFileSize) {
            throw new FileTooBigException("File {$filePath} has {$fileSize} bytes which is more than the limit of {$maxFileSize} bytes.");
        }
    }

    /**
     * Zips the file and returns the path where the zip file was created.
     * 
     * @param string $csvFilePath
     * 
     * @return string
     * 
     * @throws FilePathException
     */
    public function zipIt($csvFilePath)
    {
        $zipFilePath = str_replace('.csv', '.zip', $csvFilePath);
        $zipArchive = new \ZipArchive();

        if ($zipArchive->open($zipFilePath) === true) {
            $zipArchive->addFile($csvFilePath, 'report.csv');
            $zipArchive->close();

            return $zipFilePath;
        }

        throw new FilePathException("Could not create zip archive at {$zipFilePath}");
    }

    /**
     * @param Report $report
     * @param string $originalPath
     */
    public function moveZipToPermanentLocation(Report $report, $originalPath)
    {
        $targetPath = "{$this->getReportDir()}/{csv_reports/{$report->getId()}/{$report->setScheduleUnit()}.zip";
        $this->filePathResolver->move($originalPath, $zipPath);
    }

    /**
     * @return string
     */
    private function getReportDir()
    {
        return $this->coreParametersHelper->getParameter('report_temp_dir');
    }
}
