<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

class NarrowedOverrideParent
{
    /**
     * @param string[] $items
     */
    public function __construct($items)
    {
    }

    /**
     * @param string[] $items
     */
    private function onlyPrivate($items): void
    {
    }
}
