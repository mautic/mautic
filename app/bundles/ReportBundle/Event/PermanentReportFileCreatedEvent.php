<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Report;

class PermanentReportFileCreatedEvent extends AbstractReportEvent
{
    public function __construct(Report $report)
    {
        $this->report = $report;
    }
}
