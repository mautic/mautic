<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Scheduler\Option;

class ExportOption
{
    private readonly int $reportId;

    /**
     * @param int|null $reportId
     */
    public function __construct($reportId)
    {
        if (null !== $reportId && !is_numeric($reportId)) {
            throw new \InvalidArgumentException();
        }

        $this->reportId = (int) $reportId;
    }

    public function getReportId(): int
    {
        return $this->reportId;
    }
}
