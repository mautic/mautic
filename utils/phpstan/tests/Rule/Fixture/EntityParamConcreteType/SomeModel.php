<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\EntityParamConcreteType;

final class SomeModel
{
    public function bareObject(object $entity): void
    {
    }

    public function unionWithoutClass(object|array $entity): void
    {
    }

    public function untyped($entity): void
    {
    }

    public function nativeClassType(\stdClass $entity): void
    {
    }

    public function unionWithClass(\stdClass|int $entity): void
    {
    }

    public function nullableClass(?\stdClass $entity): void
    {
    }

    /**
     * @param \stdClass $entity
     */
    public function paramTagNarrowsObject(object $entity): void
    {
    }

    public function notEntityParam(object $value): void
    {
    }
}
