<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ReferenceFamily;

final class ReadingDispatchChild extends DispatchRoot
{
    protected function dispatch(string $action, object &$entity): string
    {
        // only reads the entity and hands it to a by-value constructor, so the reference is useless
        new \ArrayObject($entity);

        return $action.$entity->name;
    }
}
