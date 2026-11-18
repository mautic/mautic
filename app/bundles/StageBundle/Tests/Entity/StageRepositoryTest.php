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

    private const NOW = '2026-06-15 12:00:00';
    private const FUTURE = '2026-07-15 12:00:00';
    private const PAST = '2026-05-15 12:00:00';

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

    public function testGetPublishedWithContactCountsExcludesStagesOutsidePublicationWindow(): void
    {
        $now = new \DateTime(self::NOW);

        $active = (new Stage())
            ->setName('Active')
            ->setIsPublished(true)
            ->setWeight(1);
        $scheduled = (new Stage())
            ->setName('Scheduled')
            ->setIsPublished(true)
            ->setWeight(2)
            ->setPublishUp(new \DateTime(self::FUTURE));
        $expired = (new Stage())
            ->setName('Expired')
            ->setIsPublished(true)
            ->setWeight(3)
            ->setPublishDown(new \DateTime(self::PAST));
        $current = (new Stage())
            ->setName('Current')
            ->setIsPublished(true)
            ->setWeight(4)
            ->setPublishUp(new \DateTime(self::PAST))
            ->setPublishDown(new \DateTime(self::FUTURE));
        $unpublished = (new Stage())
            ->setName('Unpublished')
            ->setIsPublished(false)
            ->setWeight(5);

        $this->em->persist($active);
        $this->em->persist($scheduled);
        $this->em->persist($expired);
        $this->em->persist($current);
        $this->em->persist($unpublished);
        $this->em->flush();

        $repository = $this->em->getRepository(Stage::class);
        $this->assertInstanceOf(StageRepository::class, $repository);

        $names = array_map(
            static fn (array $row): string => $row[0]->getName(),
            $repository->getPublishedWithContactCounts($now)
        );

        $this->assertContains('Active', $names);
        $this->assertContains('Current', $names);
        $this->assertNotContains('Scheduled', $names);
        $this->assertNotContains('Expired', $names);
        $this->assertNotContains('Unpublished', $names);
    }
}
