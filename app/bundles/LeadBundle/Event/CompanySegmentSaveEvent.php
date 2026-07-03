<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\CompanySegment;

abstract class CompanySegmentSaveEvent extends CompanySegmentEvent
{
    public function __construct(CompanySegment $companySegment, private bool $isNew)
    {
        parent::__construct($companySegment);
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }
}
