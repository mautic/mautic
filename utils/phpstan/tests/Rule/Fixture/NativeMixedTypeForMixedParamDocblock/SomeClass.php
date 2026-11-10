<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeMixedTypeForMixedParamDocblock;

final class SomeClass
{
    /**
     * @param mixed $value
     */
    public function missingMixed($value): void
    {
    }

    /**
     * @param mixed $value
     */
    public function missingMixedWithNullDefault($value = null): void
    {
    }

    /**
     * @param mixed $values
     */
    public function variadicMixed(...$values): void
    {
    }

    /**
     * @param mixed $value
     */
    public function byRefMixed(&$value): void
    {
    }

    /**
     * @param mixed $value
     */
    public function skipAlreadyNative(mixed $value): void
    {
    }

    /**
     * @param string $value
     */
    public function skipNonMixed($value): void
    {
    }

    /**
     * @param mixed[] $values
     */
    public function skipMixedArray($values): void
    {
    }

    public function skipNoDocblock($value): void
    {
    }

    /**
     * @template T
     *
     * @param T $value
     */
    public function skipTemplate($value): void
    {
    }
}
