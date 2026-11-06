<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

interface SomeContract
{
    public function handle($items): void;
}
