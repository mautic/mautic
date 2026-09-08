<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

final class InterfaceConstructor
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }
}
