<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

class SkipOverride implements SomeContract
{
    /**
     * @param string[] $items
     */
    public function handle($items): void
    {
    }
}
