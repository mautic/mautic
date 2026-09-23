<?php

namespace Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Source\PlainEntity;

final class SkipEntityWithoutRepositoryClass extends KernelTestCase
{
    private EntityManagerInterface $em;

    public function testThing(): void
    {
        $repository = $this->em->getRepository(PlainEntity::class);
    }
}
