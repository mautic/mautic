<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

class ClassReplacingOwnTraitMethod
{
    use OwnConcreteTrait;

    /**
     * @param string[] $items
     */
    public function handleOwn($items): void
    {
    }
}
