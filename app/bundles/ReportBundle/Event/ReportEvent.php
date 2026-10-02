<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\ReportBundle\Entity\Report;

final class ReportEvent extends CommonEvent
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
    public function setReport(Report $report): void
    {
        $this->entity = $report;
    }
}
