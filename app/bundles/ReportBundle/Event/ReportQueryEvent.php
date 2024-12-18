<?php

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ReportBundle\Entity\Report;

class ReportQueryEvent extends AbstractReportEvent
{
    private int $totalResults;

    public function __construct(
        Report $report,
        private QueryBuilder $query,
        $totalResults,
        private array $options,
    ) {
        $this->context      = $report->getSource();
        $this->report       = $report;
        $this->totalResults = (int) $totalResults;
    }

    /**
     * @return QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param QueryBuilder $query
     */
    public function setQuery($query): void
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

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }
}
