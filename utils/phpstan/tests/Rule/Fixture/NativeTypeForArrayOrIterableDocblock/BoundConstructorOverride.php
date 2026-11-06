<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

// an abstract (or interface) constructor is a binding signature contract, so adding the native
// type to the override would fatal; the narrowing child constructor is left untouched
class BoundConstructorOverride extends AbstractNarrowedOverrideParent
{
    /**
     * @param string[] $items
     */
    public function __construct($items)
    {
    }
}
