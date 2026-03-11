<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\CompanySegment;

abstract class CompanySegmentPreSavedEvent extends CompanySegmentEvent
{
    public function __construct(CompanySegment $companySegment, EntityManagerInterface $entityManager, private bool $isNew)
    {
        parent::__construct($companySegment, $entityManager);
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }
}
