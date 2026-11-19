<?php

namespace Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Fixture;

use Doctrine\ORM\EntityManagerInterface;

final class SkipDynamicEntityClass
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function repositoryFor(string $entityClass): object
    {
        return $this->entityManager->getRepository($entityClass);
    }
}
