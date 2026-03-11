<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\CompanySegment;
use Symfony\Contracts\EventDispatcher\Event;

abstract class CompanySegmentEvent extends Event
{
    public function __construct(private CompanySegment $companySegment, private EntityManagerInterface $entityManager)
    {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getCompanySegment(): CompanySegment
    {
        return $this->companySegment;
    }
}
