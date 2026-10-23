<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\EntityParamMustHaveType;

final class SomeClass
{
    public function missingType($entity): void
    {
    }

    public function missingTypeUpperCase($Entity): void
    {
    }

    public function mixedNativeType(mixed $entity): void
    {
    }

    public function scalarNativeType(int $entity): void
    {
    }

    public function arrayNativeType(array $entity): void
    {
    }

    /**
     * a "@param" tag alone is not enough, a native type is required
     *
     * @param \stdClass $entity
     */
    public function paramTagOnly($entity): void
    {
    }

    public function skipObjectType(object $entity): void
    {
    }

    public function skipClassType(\stdClass $entity): void
    {
    }

    public function skipUnionType(object|array $entity): void
    {
    }

    public function skipNullableObjectType(?object $entity): void
    {
    }

    public function testMethodIsExempt(mixed $entity): void
    {
    }
}
