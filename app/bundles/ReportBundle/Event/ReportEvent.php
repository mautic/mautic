<?php

namespace Mautic\ReportBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\ReportBundle\Entity\Report;

/**
 * Class ReportEvent.
 */
class ReportEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(Report $report, $isNew = false)
    {
        $this->entity = $report;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Report entity.
     *
     * @return Report
     */
    public function getReport()
    {
        return $this->entity;
    }

    /**
     * Sets the Report entity.
     */
    public function setReport(Report $report)
    {
        $this->entity = $report;
    }
}
