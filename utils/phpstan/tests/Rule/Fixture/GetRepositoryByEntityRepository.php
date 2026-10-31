<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

// the repository is fetched by an entity constant - must be reported
final class GetRepositoryByEntityRepository
{
    /**
     * @var object
     */
    private $entityManager;

    public function run(): void
    {
        $this->entityManager->getRepository(\stdClass::class);
    }

    public function byString(): void
    {
        // string name is not an entity constant, nothing to report
        $this->entityManager->getRepository('MauticLeadBundle:Lead');
    }
}
