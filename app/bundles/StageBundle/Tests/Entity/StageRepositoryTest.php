<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Entity\StageRepository;

final class StageRepositoryTest extends MauticMysqlTestCase
{
    public function testGetEntitiesReturnsContactCount(): void
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

        $repository = $this->em->getRepository(Stage::class);
        \assert($repository instanceof StageRepository);

        $results = $repository->getEntities([
            'withContactCount' => true,
            'ignore_paginator' => true,
        ]);

        $this->assertCount(2, $results);

        $counts = [];
        foreach ($results as $row) {
            $stage = $row[0];
            \assert($stage instanceof Stage);
            $counts[$stage->getName()] = (int) $row['contactCount'];
        }

        $this->assertSame(2, $counts['Stage A']);
        $this->assertSame(0, $counts['Stage B']);
    }
}
