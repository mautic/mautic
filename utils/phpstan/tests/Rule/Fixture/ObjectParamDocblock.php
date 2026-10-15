<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class ObjectParamDocblock
{
    /**
     * @param SomeValueObject $item
     */
    public function methodCalledOnObject($item): void
    {
        $item->doThing();
    }

    /**
     * @param SomeValueObject $item
     */
    public function noMethodCall($item): void
    {
        $copy = $item;
    }

    /**
     * @param SomeValueObject|null $item
     */
    public function nullableObject($item): void
    {
        $item->doThing();
    }

    /**
     * @param string $name
     */
    public function scalar($name): string
    {
        return $name;
    }

    public function alreadyTyped(SomeValueObject $item): void
    {
        $item->doThing();
    }

    /**
     * @param SomeValueObject $item
     */
    public function hasDefaultValue($item = null): void
    {
        $item->doThing();
    }

    /**
     * @param SomeValueObject $item
     */
    public function checksInstanceof($item): void
    {
        if ($item instanceof SomeValueObject) {
            $item->doThing();
        }
    }
}
