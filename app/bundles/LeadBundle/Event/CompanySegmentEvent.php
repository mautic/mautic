<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\CompanySegment;
use Symfony\Contracts\EventDispatcher\Event;

abstract class CompanySegmentEvent extends Event
{
    public function __construct(private CompanySegment $companySegment)
    {
    }

    public function getCompanySegment(): CompanySegment
    {
        return $this->companySegment;
    }
}
