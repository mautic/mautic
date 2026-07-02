<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

final class CompanySegmentFilteringEvent extends Event
{
    private bool $isFilteringDone;

    private string $subQuery;

    private string $companiesTableAlias;

    public function __construct(
        private ContactSegmentFilterCrate $details,
        private string $alias,
        private QueryBuilder $queryBuilder,
    ) {
        $this->isFilteringDone = false;
        $this->subQuery        = '';
        $preTableName          = '';
        if (is_string(MAUTIC_TABLE_PREFIX)) {
            $preTableName = MAUTIC_TABLE_PREFIX;
        }
        $tableAlias            = $queryBuilder->getTableAlias($preTableName.'companies');
        \assert(is_string($tableAlias));
        $this->companiesTableAlias = $tableAlias;
    }

    public function getDetails(): ContactSegmentFilterCrate
    {
        return $this->details;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFunc(): string
    {
        $detailsList = $this->details->getArray();
        if (!array_key_exists('operator', $detailsList) || !is_string($detailsList['operator'])) {
            return '';
        }

        return $detailsList['operator'];
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setFilteringStatus(bool $status): void
    {
        $this->isFilteringDone = $status;
    }

    public function setSubQuery(string $query): void
    {
        $this->subQuery = $query;

        $this->setFilteringStatus(true);
    }

    public function isFilteringDone(): bool
    {
        return $this->isFilteringDone;
    }

    public function getSubQuery(): string
    {
        return $this->subQuery;
    }

    public function setDetails(ContactSegmentFilterCrate $details): void
    {
        $this->details = $details;
    }

    public function getCompaniesTableAlias(): string
    {
        return $this->companiesTableAlias;
    }
}
