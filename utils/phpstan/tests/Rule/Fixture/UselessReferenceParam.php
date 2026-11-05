<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class UselessReferenceParam
{
    public function onlyReadsAndMutatesObject(string $action, object &$entity): string
    {
        $entity->touched = true;

        return $action;
    }

    // passing a variable to a by-value constructor copies it, the reference changes nothing
    public function passedToByValueConstructor(array &$payload): void
    {
        new \ArrayObject($payload);
    }
}
