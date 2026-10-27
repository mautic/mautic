<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Report;

class ReportDataEvent extends AbstractReportEvent
{
    private readonly int $totalResults;

    public function __construct(
        Report $report,
        private array $data,
        $totalResults,
        private array $options,
    ) {
        $this->context      = $report->getSource();
        $this->report       = $report;
        $this->totalResults = (int) $totalResults;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    public function updateColumnType(string $alias, string $type): void
    {
        foreach ($this->options['columns'] as &$column) {
            if ($column['alias'] === $alias) {
                $column['type'] = $type;
                break;
            }
        }
    }
}
