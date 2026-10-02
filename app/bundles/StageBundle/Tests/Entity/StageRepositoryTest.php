<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Entity\StageRepository;
use Mautic\StageBundle\Tests\_helpers\StageTestSeederTrait;

final class StageRepositoryTest extends MauticMysqlTestCase
{
    use StageTestSeederTrait;

    public function testGetEntitiesReturnsContactCount(): void
    {
        $this->seedStagesWithLeads();

        $repository = $this->em->getRepository(Stage::class);
        $this->assertInstanceOf(StageRepository::class, $repository);

        $results = $repository->getEntities([
            'withContactCount' => true,
            'ignore_paginator' => true,
        ]);

        $this->assertCount(2, $results);

        $counts = [];
        foreach ($results as $row) {
            $stage = $row[0];
            $this->assertInstanceOf(Stage::class, $stage);
            $counts[$stage->getName()] = (int) $row['contactCount'];
        }

        $this->assertSame(2, $counts['Stage A']);
        $this->assertSame(0, $counts['Stage B']);
    }
}
