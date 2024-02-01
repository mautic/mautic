<?php

namespace Mautic\ReportBundle\Scheduler\Option;

class ExportOption
{
    private int $reportId;

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

    public function getReportId(): int
    {
        return $this->reportId;
    }
}
