<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

/**
 * @param string[] $items
 */
function missingArrayParamFunction($items): void
{
}

/**
 * @return iterable<int>
 */
function missingIterableReturnFunction()
{
    yield 1;
}

/**
 * @param string[] $items
 */
function skipNativeArrayFunction(array $items): void
{
}
