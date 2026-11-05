<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

// a concrete parent constructor is LSP-exempt and a private parent method imposes no contract,
// so both narrowing child methods must still be reported
class NarrowedOverride extends NarrowedOverrideParent
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
