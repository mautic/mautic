<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeUnionParamDocblock;

final class SomeFormFactory
{
    /**
     * @param SomeEntity $entity
     */
    public function createNarrowed(object|array $entity): void
    {
    }

    /**
     * @param int $id
     */
    public function createNarrowedScalar(int|string $id): void
    {
    }

    /**
     * @param SomeEntity|array<string, mixed> $entity
     */
    public function createUnionDocblock(object|array $entity): void
    {
    }

    public function createNoDocblock(object|array $entity): void
    {
    }

    /**
     * @param SomeEntity $entity
     */
    public function skipNullableNativeType(?SomeEntity $entity): void
    {
    }

    /**
     * @param mixed $entity
     */
    public function skipMixedDocblock(object|array $entity): void
    {
    }
}
