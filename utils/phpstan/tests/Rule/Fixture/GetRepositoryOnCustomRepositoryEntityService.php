<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class GetRepositoryOnCustomRepositoryEntityService
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function onRegistry(): void
    {
        $this->doctrine->getRepository(EntityWithCustomRepository::class);
    }

    public function onEntityManager(): void
    {
        $this->entityManager->getRepository(EntityWithCustomRepository::class);
    }

    public function entityWithoutCustomRepository(): void
    {
        // no custom repository declared, nothing to report
        $this->doctrine->getRepository(EntityWithoutCustomRepository::class);
    }

    public function notAnEntityConstant(): void
    {
        // a string name is not an entity constant, nothing to report
        $this->doctrine->getRepository('MauticLeadBundle:Lead');
    }
}
