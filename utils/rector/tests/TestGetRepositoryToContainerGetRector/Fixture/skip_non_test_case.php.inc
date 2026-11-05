<?php

namespace Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Source\SomeEntity;

final class SkipNonTestCase
{
    private EntityManagerInterface $em;

    public function findThing(): object
    {
        return $this->em->getRepository(SomeEntity::class);
    }
}
