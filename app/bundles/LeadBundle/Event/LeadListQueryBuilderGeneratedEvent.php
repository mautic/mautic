<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

class LeadListQueryBuilderGeneratedEvent extends Event
{
    public function __construct(
        private LeadList $segment,
        private QueryBuilder $queryBuilder,
    ) {
    }

    /**
     * @return LeadList
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }
}
