<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

abstract class AbstractNarrowedOverrideParent
{
    /**
     * @param string[] $items
     */
    abstract public function __construct($items);
}
