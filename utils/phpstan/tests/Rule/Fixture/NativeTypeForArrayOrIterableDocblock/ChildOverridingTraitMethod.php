<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

class ChildOverridingTraitMethod extends ParentUsingTrait
{
    /**
     * @param string[] $items
     */
    public function handleFromTrait($items): void
    {
    }
}
