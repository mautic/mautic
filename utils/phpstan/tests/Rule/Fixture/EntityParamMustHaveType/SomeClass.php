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

    public function skipNativeType(\stdClass $entity): void
    {
    }

    public function skipObjectType(object $entity): void
    {
    }

    /**
     * @param \stdClass $entity
     */
    public function skipParamTag($entity): void
    {
    }

    /**
     * @param string $name
     */
    public function missingTypeWithOtherParamTag($name, $entity): void
    {
    }

    public function skipOtherName($name): void
    {
    }
}
