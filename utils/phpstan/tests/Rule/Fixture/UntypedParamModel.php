<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

class UntypedParamModel
{
    public function deleteEntity($entity): void
    {
    }

    public function typedEntity(object $entity): void
    {
    }

    public function getEntity($id)
    {
    }

    public function deleteEntities($ids): void
    {
    }

    public function noParams(): void
    {
    }

    public function twoParams($first, $second): void
    {
    }

    protected function protectedUntyped($entity): void
    {
    }

    private function privateUntyped($entity): void
    {
    }
}
