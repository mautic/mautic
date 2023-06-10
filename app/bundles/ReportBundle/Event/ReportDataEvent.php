<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Report;

/**
 * Class ReportDataEvent.
 */
class ReportDataEvent extends AbstractReportEvent
{
    /**
     * @var int
     */
    private $totalResults = 0;

    /**
     * ReportDataEvent constructor.
     */
    public function __construct(Report $report, private array $data, $totalResults, private array $options)
    {
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
     *
     * @return ReportDataEvent
     */
    public function setData($data)
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
