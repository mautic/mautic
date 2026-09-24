<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

class SomeRepositoryHolder
{
    /**
     * @param class-string $entityClass
     */
    public function getRepository(string $entityClass): void
    {
    }
}
