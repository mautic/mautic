<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ReportBundle\Entity\Report;

final class ReportFilterConditionEvent extends AbstractReportEvent
{
    private CompositeExpression|string|null $condition = null;

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $filterDefinition
     */
    public function __construct(
        Report $report,
        private readonly array $filter,
        private readonly array $filterDefinition,
        private readonly QueryBuilder $queryBuilder,
    ) {
        $this->report  = $report;
        $this->context = $report->getSource();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilterDefinition(): array
    {
        return $this->filterDefinition;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getCondition(): CompositeExpression|string|null
    {
        return $this->condition;
    }

    public function setCondition(CompositeExpression|string $condition): void
    {
        $this->condition = $condition;
        $this->stopPropagation();
    }
}
