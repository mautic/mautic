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

    public function getQuery(): QueryBuilder
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

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }
}
