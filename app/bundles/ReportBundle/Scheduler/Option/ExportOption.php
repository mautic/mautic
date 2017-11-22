<?php

namespace Mautic\ReportBundle\Scheduler\Option;

class ExportOption
{
    /**
     * @var int
     */
    private $reportId;

    /**
     * @param int|null $reportId
     */
    public function __construct($reportId)
    {
        if (!is_null($reportId) && !is_numeric($reportId)) {
            throw new \InvalidArgumentException();
        }

        $this->reportId = (int) $reportId;
    }

    /**
     * @return int
     */
    public function getReportId()
    {
        return $this->reportId;
    }
}
