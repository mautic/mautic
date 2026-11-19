<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\EntityManagerInterface;

final class ServiceInCreateMethod
{
    public function createForEntityManager(EntityManagerInterface $entityManager): object
    {
        return $entityManager->getConnection();
    }
}
