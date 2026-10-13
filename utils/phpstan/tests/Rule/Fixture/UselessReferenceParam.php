<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

class UselessReferenceParam
{
    private function onlyReadsAndMutatesObject(string $action, object &$entity): string
    {
        $entity->touched = true;

        return $action;
    }

    // skipped: a public method may be overridden by a subclass that needs the reference
    public function overridablePublic(\stdClass &$object): void
    {
        echo $object->value;
    }
}
