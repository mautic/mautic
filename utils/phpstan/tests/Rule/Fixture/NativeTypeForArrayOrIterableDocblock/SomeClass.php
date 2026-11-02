<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableDocblock;

final class SomeClass
{
    /**
     * @param string[] $items
     */
    public function missingArrayParam($items): void
    {
    }

    /**
     * @param iterable<int> $values
     */
    public function missingIterableParam($values): void
    {
    }

    /**
     * @param string[] $items
     */
    public function mixedArrayParam(mixed $items): void
    {
    }

    /**
     * @return string[]
     */
    public function missingArrayReturn()
    {
        return [];
    }

    /**
     * @return iterable<int>
     */
    public function missingIterableReturn()
    {
        yield 1;
    }

    /**
     * @param string[] $items
     */
    public function skipNativeArray(array $items): void
    {
    }

    /**
     * @return string[]
     */
    public function skipNativeArrayReturn(): array
    {
        return [];
    }

    /**
     * @param string $name
     */
    public function skipScalar($name): void
    {
    }

    /**
     * @param string[] $items
     */
    public function skipVariadic(string ...$items): void
    {
    }

    public function skipNoDocblock($items): void
    {
    }

    /**
     * @param string[] $items
     */
    public function skipByRef(&$items): void
    {
    }
}
