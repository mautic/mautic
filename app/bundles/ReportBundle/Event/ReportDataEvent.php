<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Report;

class ReportDataEvent extends AbstractReportEvent
{
    private int $totalResults;

    public function __construct(
        Report $report,
        private array $data,
        $totalResults,
        private array $options
    ) {
        $this->context      = $report->getSource();
        $this->report       = $report;
        $this->totalResults = (int) $totalResults;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }
}
