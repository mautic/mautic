<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;

final class ConcreteClassConstructor
{
    public function __construct(
        private readonly Router $router,
        private readonly EntityManager $entityManager,
    ) {
    }
}
