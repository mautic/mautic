<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ReferenceFamily;

final class WritingDispatchChild extends DispatchRoot
{
    protected function dispatch(string $action, object &$entity): string
    {
        // reassigns the entity, so the reference is mandated for the whole family
        $entity = new \stdClass();

        return $action;
    }
}
