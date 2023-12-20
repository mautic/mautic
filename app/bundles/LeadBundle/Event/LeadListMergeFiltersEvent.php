<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

class LeadListMergeFiltersEvent extends CommonEvent
{
    /**
     * @var mixed[]
     */
    private array $filters;

    /**
     * @param mixed[] $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
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
