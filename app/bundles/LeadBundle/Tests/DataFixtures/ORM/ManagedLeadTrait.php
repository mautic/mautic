<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\Lead;

trait ManagedLeadTrait
{
    private function getManagedLead(Lead $lead, ObjectManager $manager): Lead
    {
        \assert($manager instanceof EntityManagerInterface);
        $managedLead = $manager->getReference(Lead::class, $lead->getId());
        \assert($managedLead instanceof Lead);

        return $managedLead;
    }
}
