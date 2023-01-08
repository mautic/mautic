<?php

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ReportBundle\Entity\Report;

/**
 * Class ReportDataEvent.
 */
class ReportQueryEvent extends AbstractReportEvent
{
    /**
     * @var QueryBuilder
     */
    private $query;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var int
     */
    private $totalResults = 0;

    /**
     * ReportDataEvent constructor.
     *
     * @param $totalResults
     */
    public function __construct(Report $report, QueryBuilder $query, $totalResults, array $options)
    {
        $this->context      = $report->getSource();
        $this->report       = $report;
        $this->query        = $query;
        $this->options      = $options;
        $this->totalResults = (int) $totalResults;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param QueryBuilder $query
     *
     * @return ReportDataEvent
     */
    public function setQuery($query)
    {
        $this->query = $query;
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
