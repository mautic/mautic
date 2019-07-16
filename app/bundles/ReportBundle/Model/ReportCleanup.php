<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Model;

use Mautic\ReportBundle\Scheduler\Model\FileHandler;

class ReportCleanup
{
    /**
     * @const int KEEP_FILE_DAYS
     */
    const KEEP_FILE_DAYS = 7;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @param FileHandler $fileHandler
     */
    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    /**
     * @param int $reportId
     */
    public function cleanup($reportId)
    {
        $this->fileHandler->deleteCompressedCsvFileForReportId($reportId);
    }

    /**
     * Deletes files older than KEEP_FILE_DAYS.
     */
    public function cleanupAll()
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

    /**
     * @param string $filePath
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function shouldBeDeleted($filePath)
    {
        $created = new \DateTime(date('Y-m-d', filemtime($filePath)));
        $now     = new \DateTime();
        $days    = $created->diff($now)->days;

        if ($days >= self::KEEP_FILE_DAYS) {
            return true;
        }

        return false;
    }
}
