<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ServiceInTestCaseMethod extends TestCase
{
    private function createRecord(EntityManagerInterface $entityManager): void
    {
        $entityManager->flush();
    }
}
