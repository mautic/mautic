<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\_helpers;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Stage;

/**
 * Reusable seeding helpers for Stage tests.
 */
trait StageTestSeederTrait
{
    protected function seedStagesWithLeads(): void
    {
        $stageA = (new Stage())
            ->setName('Stage A')
            ->setIsPublished(true);
        $stageB = (new Stage())
            ->setName('Stage B')
            ->setIsPublished(true);

        $this->em->persist($stageA);
        $this->em->persist($stageB);
        $this->em->flush();

        $lead1 = (new Lead())
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('john@example.com')
            ->setStage($stageA);
        $lead2 = (new Lead())
            ->setFirstname('Jane')
            ->setLastname('Roe')
            ->setEmail('jane@example.com')
            ->setStage($stageA);

        $this->em->persist($lead1);
        $this->em->persist($lead2);
        $this->em->flush();
    }
}
