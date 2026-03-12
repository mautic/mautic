<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

/**
 * The event is dispatched right after a company segment is removed.
 */
class CompanySegmentPostDelete extends CompanySegmentEvent
{
    /**
     * @return array<mixed>
     */
    public function getChanges(): array
    {
        return $this->getCompanySegment()->getChanges();
    }
}
