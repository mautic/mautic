<?php

namespace Mautic\ReportBundle\Scheduler\Option;

class ExportOption
{
    /**
     * @var int
     */
    private $rowLimit;

    /**
     * @var int
     */
    private $reportId;

    /**
     * @param int|null $rowLimit
     * @param int|null $reportId
     */
    public function __construct($rowLimit, $reportId)
    {
        if ((!is_null($rowLimit) && !is_numeric($rowLimit)) || (!is_null($reportId) && !is_numeric($reportId))) {
            throw new \InvalidArgumentException();
        }

        $this->rowLimit = (int) $rowLimit;
        $this->reportId = (int) $reportId;
    }

    /**
     * @return int
     */
    public function getRowLimit()
    {
        return $this->rowLimit;
    }

    /**
     * @return int
     */
    public function getReportId()
    {
        return $this->reportId;
    }
}
