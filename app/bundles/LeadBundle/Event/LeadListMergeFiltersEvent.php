<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

class LeadListMergeFiltersEvent extends CommonEvent
{
    /**
     * @param mixed[] $filters
     */
    public function __construct(private array $filters)
    {
    }

    /**
     * @return mixed[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param mixed[] $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }
}
