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

    public function getSegment(): \Mautic\LeadBundle\Entity\LeadList
    {
        return $this->segment;
    }

    public function getQueryBuilder(): \Mautic\LeadBundle\Segment\Query\QueryBuilder
    {
        return $this->queryBuilder;
    }
}
