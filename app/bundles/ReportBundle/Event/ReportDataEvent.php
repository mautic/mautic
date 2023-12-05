<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Report;

class ReportDataEvent extends AbstractReportEvent
{
    private array $data;

    private array $options;

    private int $totalResults;

    public function __construct(Report $report, array $data, $totalResults, array $options)
    {
        $this->context      = $report->getSource();
        $this->report       = $report;
        $this->data         = $data;
        $this->options      = $options;
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

    /**
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }
}
