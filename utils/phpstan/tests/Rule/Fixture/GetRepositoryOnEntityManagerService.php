<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\EntityManagerInterface;

class GetRepositoryOnEntityManagerService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function byProperty(): void
    {
        $this->entityManager->getRepository(\stdClass::class);
    }

    public function byVariable(EntityManagerInterface $entityManager): void
    {
        $entityManager->getRepository(\stdClass::class);
    }

    public function byString(): void
    {
        // a string name is not an entity constant, nothing to report
        $this->entityManager->getRepository('MauticLeadBundle:Lead');
    }

    public function onAnotherService(SomeRepositoryHolder $someRepositoryHolder): void
    {
        // not an entity manager, nothing to report
        $someRepositoryHolder->getRepository(\stdClass::class);
    }
}
