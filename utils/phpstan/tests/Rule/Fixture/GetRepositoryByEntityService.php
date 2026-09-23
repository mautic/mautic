<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

// not a repository - must be skipped
final class GetRepositoryByEntityService
{
    /**
     * @var object
     */
    private $entityManager;

    public function run(): void
    {
        $this->entityManager->getRepository(\stdClass::class);
    }
}
