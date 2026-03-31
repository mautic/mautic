<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

class CompanySegmentPostSave extends CompanySegmentSaveEvent
{
    /**
     * @return array<mixed>
     */
    public function getChanges(): array
    {
        return $this->getCompanySegment()->getChanges();
    }
}
